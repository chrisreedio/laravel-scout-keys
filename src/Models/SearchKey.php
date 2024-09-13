<?php

namespace ChrisReedIO\ScoutKeys\Models;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Scout\Engines\MeilisearchEngine;
use Meilisearch\Exceptions\ApiException;

use function app;
use function array_keys;
use function is_null;
use function now;

/**
 * Class SearchKey
 *
 * @property int $id
 * @property int $user_id
 * @property string $uuid
 * @property string|null $key
 * @property Carbon|null $expires_at
 * @property string|null $tenant_token
 * @property Carbon|null $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read \App\Models\User $user
 *
 * @method static Builder|SearchKey active()
 * @method static Builder|SearchKey expired()
 */
class SearchKey extends Model
{
    use Prunable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'uuid',
        'key',
        'expires_at',
        'tenant_token',
    ];

    protected $casts = [
        'uuid' => 'string',
        'expires_at' => 'datetime',
    ];

    // Setup a booting hook for the creating event
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SearchKey $key) {
            $key->uuid = $key->uuid ?: (string) Str::uuid();
            $keyLifeMinutes = config('secure-meilisearch.key.lifetime');
            $key->expires_at = now()->addMinutes($keyLifeMinutes);
        });

        // static::deleting(function (SearchKey $key) {
        //     $key->revoke();
        // });
    }

    //region Relationships

    /**
     * The user that owns the search key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    //endregion

    //region Scopes
    public function scopeActive($query): void
    {
        $query->where('expires_at', '>', now());
    }

    public function scopeExpired($query): void
    {
        $query->where('expires_at', '<', now());
    }
    //endregion

    /**
     * Create a search key in MeiliSearch.
     * This should populate the local key field.
     */
    public function request(): ?string
    {
        if (is_null($this->uuid)) {
            Log::critical('Attempting to create a search key without a UUID.', ['key' => $this]);

            return null;
        }

        if (! is_null($this->key)) {
            Log::warning('Attempting to request a search key that already has a key.', ['key' => $this]);

            return $this->key;
        }
        // try {
        /** @var MeilisearchEngine $engine */
        $engine = app(MeilisearchEngine::class);

        $expiresAt = $this->expires_at->toDateTime();

        // Auto discover the index names from the meilisearch config settings
        // TODO - Allow this to be configurable later
        $indexes = array_keys(config('scout.meilisearch.index-settings'));
        $keyOptions = [
            'name' => 'Generated User Search Key',
            'indexes' => $indexes,
            'actions' => ['search'],
            'expiresAt' => $expiresAt->format('c'),
            'uid' => $this->uuid,
        ];

        // dd($keyOptions);
        // Generate API Key
        /** @phpstan-ignore-next-line */
        $this->key = $engine->createKey($keyOptions)->getKey();

        // Now lets generate the Tenant Token
        $tenantOptions = [
            'apiKey' => $this->key,
            'expiresAt' => $expiresAt,
        ];

        // Construct search rules so that users with the 'special.view' permission have no filters.
        // Users without this role will have a filter of 'special = false'.
        $searchRules = (object) [
            // TODO: Special filter feature.
            //     Model::searchableIndex() => (object) [
            //         'filter' => $this->user->cannot('special.view') ? 'special = false' : '',
            //     ],
        ];

        /** @phpstan-ignore-next-line */
        $this->tenant_token = $engine->generateTenantToken($this->uuid, $searchRules, $tenantOptions);
        $this->save();
        // } catch (Exception $e) {
        //     Log::warning('Failed to create search key.', ['key' => $this, 'error' => $e]);
        //     // throw $e;
        // }

        return $this->key;
    }

    /**
     * Removes this search key from MeiliSearch.
     */
    public function revoke(): bool
    {
        if (is_null($this->uuid)) {
            Log::critical('Attempting to revoke a search key without a UUID.', ['key' => $this]);

            return false;
        }

        /** @var MeilisearchEngine $engine */
        $engine = app(MeilisearchEngine::class);
        try {
            $results = $engine->deleteKey($this->uuid);
            if (empty($results)) {
                if ($this->forceDelete()) {
                    // If we get a valid response from Meilisearch, and we deleted the key, then we're good
                    return true;
                }
            }
        } catch (ApiException $e) {
            if ($e->httpStatus == 404) {
                // If the key doesn't exist, then we're good.
                Log::warning("Attempting to revoke a search key that doesn't exist.", ['key' => $this]);
                if ($this->delete()) {
                    return true;
                }
            }
            Log::error('Error revoking search key.', ['key' => $this, 'error' => $e]);

            return false;
        }

        return false;
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::query()->where('deleted_at', '<=', now()->subWeek());
    }
}
