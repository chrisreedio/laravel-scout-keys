<?php

namespace ChrisReedIO\ScoutKeys\Models;

use App\Models\User;
use ChrisReedIO\ScoutKeys\Contracts\ScoutEngine;
use ChrisReedIO\ScoutKeys\Contracts\SearchUser;
use ChrisReedIO\ScoutKeys\Enums\ScoutEngineType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

use function now;

/**
 * Class SearchKey
 *
 * @property int $id
 *                   // * @property int $user_id
 * @property ScoutEngineType $engine
 * @property int $engine_key_id
 * @property string $keyable_type
 * @property int $keyable_id
 * @property string $uuid
 * @property string|null $key
 * @property string|null $scoped_key
 * @property Carbon|null $expires_at
 * @property Carbon|null $deleted_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read SearchUser $keyable
 *
 * @method static Builder|SearchKey active()
 * @method static Builder|SearchKey expired()
 */
class SearchKey extends Model
{
    use Prunable, SoftDeletes;

    protected $fillable = [
        // 'user_id',
        'engine',
        'engine_key_id',
        'keyable_type',
        'keyable_id',
        'uuid',
        'key',
        'scoped_key',
        'expires_at',
    ];

    protected $casts = [
        'engine' => ScoutEngineType::class,
        'uuid' => 'string',
        'expires_at' => 'datetime',
    ];

    // Set up a booting hook for the creating event
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SearchKey $key) {
            $key->uuid = $key->uuid ?: (string) Str::uuid();
            $keyLifeMinutes = (int) config('scout-keys.key.lifetime');
            $key->expires_at = now()->addMinutes($keyLifeMinutes);
            // Engine Detection
            $key->engine = ScoutEngineType::detect();
        });

        // static::deleting(function (SearchKey $key) {
        //     $key->revoke();
        // });
    }

    //region Relationships

    /**
     * The user that owns the search key.
     */
    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class);
    // }
    public function keyable(): MorphTo
    {
        return $this->morphTo();
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
        /** @var ?ScoutEngine $scoutAdapter */
        $scoutAdapter = $this->engine->getAdapter();

        return ($scoutAdapter)::generateScopedKey($this);
    }

    /**
     * Removes this search key from MeiliSearch.
     */
    public function revoke(): bool
    {
        /** @var ?ScoutEngine $scoutAdapter */
        $scoutAdapter = $this->engine->getAdapter();

        return ($scoutAdapter)::revokeKey($this);
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::query()->where('deleted_at', '<=', now()->subWeek());
    }
}
