<?php

namespace ChrisReedIO\ScoutKeys\Classes\Adapters;

use ChrisReedIO\ScoutKeys\Contracts\ScoutEngine;
use ChrisReedIO\ScoutKeys\Models\SearchKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Scout\Engines\MeilisearchEngine;
use Meilisearch\Exceptions\ApiException;

use function app;
use function array_keys;
use function config;
use function is_null;

class MeilisearchAdapter implements ScoutEngine
{
    public static function generateScopedKey(SearchKey $key): ?string
    {
        if (! is_null($key->key)) {
            Log::warning('Attempting to request a search key that already has a key.', ['key' => $key]);

            return $key->key;
        }

        /** @var MeilisearchEngine $engine */
        $engine = app(MeilisearchEngine::class);

        $key->uuid ??= (string) Str::uuid();
        $expiresAt = $key->expires_at->toDateTime();

        // Auto discover the index names from the meilisearch config settings
        // TODO - Allow this to be configurable later
        $indexes = array_keys(config('scout.meilisearch.index-settings'));
        $keyOptions = [
            'name' => 'Generated User Search Key',
            'indexes' => $indexes,
            'actions' => ['search'],
            'expiresAt' => $expiresAt->format('c'),
            'uid' => $key->uuid,
        ];

        // dd($keyOptions);
        // Generate API Key
        /** @phpstan-ignore-next-line */
        $key->key = $engine->createKey($keyOptions)->getKey();

        // Now lets generate the Tenant Token
        $tenantOptions = [
            'apiKey' => $key->key,
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
        $key->scoped_key = $engine->generateTenantToken($key->uuid, $searchRules, $tenantOptions);
        $key->save();

        return $key->scoped_key;
    }

    public static function revokeKey(SearchKey $key): bool
    {
        if (is_null($key->uuid)) {
            Log::critical('Attempting to revoke a search key without a UUID.', ['key' => $key]);

            return false;
        }

        /** @var MeilisearchEngine $engine */
        $engine = app(MeilisearchEngine::class);
        try {
            $results = $engine->deleteKey($key->uuid);
            if (empty($results)) {
                if ($key->forceDelete()) {
                    // If we get a valid response from Meilisearch, and we deleted the key, then we're good
                    return true;
                }
            }
        } catch (ApiException $e) {
            if ($e->httpStatus == 404) {
                // If the key doesn't exist, then we're good.
                Log::warning("Attempting to revoke a search key that doesn't exist.", ['key' => $key]);
                if ($key->delete()) {
                    return true;
                }
            }
            Log::error('Error revoking search key.', ['key' => $key, 'error' => $e]);

            return false;
        }

        return false;
    }
}
