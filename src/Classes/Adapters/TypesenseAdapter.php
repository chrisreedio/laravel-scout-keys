<?php

namespace ChrisReedIO\ScoutKeys\Classes\Adapters;

use ChrisReedIO\ScoutKeys\Contracts\ScoutEngine;
use ChrisReedIO\ScoutKeys\Models\SearchKey;
use Exception;
use Illuminate\Support\Facades\Log;
use JsonException;
use Typesense\Client as Typesense;

use Typesense\Exceptions\ConfigError;
use Typesense\Exceptions\TypesenseClientError;
use function array_keys;
use function array_map;
use function config;
use function dd;
use function is_null;

class TypesenseAdapter implements ScoutEngine
{
    /**
     * @throws \Http\Client\Exception
     * @throws TypesenseClientError
     * @throws ConfigError
     * @throws JsonException
     */
    public static function generateScopedKey(SearchKey $key): ?string
    {
        if (! is_null($key->scoped_key)) {
            Log::warning('Attempting to request a search key that already has a key.', ['key' => $key]);

            return $key->scoped_key;
        }

        // Auto discover the index names from the typesense config settings
        // TODO - Allow this to be configurable later
        $indexes = array_keys(config('scout.typesense.model-settings'));
        // Remap the class names to index names
        $indexes = array_map(fn ($index) => (new $index)->searchableAs(), $indexes);

        if (empty($indexes)) {
            throw new ConfigError('No configured Typesense collections found in the Scout Config', 404);
        }

        // Get the typesense client
        $typesense = new Typesense(config('scout.typesense.client-settings'));
        $result = $typesense->keys->create([
            'description' => 'User search key: '.$key->uuid,
            'actions' => ['documents:search'],
            'collections' => $indexes,
            'expires_at' => $key->expires_at->timestamp,
            'autodelete' => true,
        ]);
        // dd($result);
        $key->engine_key_id = $result['id'];
        $key->key = $result['value'];
        // dd($engineManager->getDefaultDriver());

        $keyOptions = [
            'filter_by' => $key->keyable_type.'_id:'.$key->keyable_id,
            'exclude_fields' => ['password'],
        ];

        // Local Scoped Key Generation
        $key->scoped_key = $typesense->keys->generateScopedSearchKey($key->key, $keyOptions);
        $key->save();
        // dd($key->toArray());
        // dd($keyOptions);

        return $key->scoped_key;
    }

    public static function revokeKey(SearchKey $key): bool
    {
        if (is_null($key->engine_key_id)) {
            Log::warning('Attempting to revoke a search key that does not have an engine key.', ['key' => $key]);

            return false;
        }

        // Get the typesense client
        $typesense = new Typesense(config('scout.typesense.client-settings'));
        try {
            $results = $typesense->keys[$key->engine_key_id]->delete();
            // if (empty($results)) {
            // Log::error('Failed to revoke search key. Already Deleted?', ['key' => $key]);
            // }

            $key->delete();

            return true;
        } catch (Exception $e) {
            Log::error('Failed to revoke search key.', ['key' => $key, 'error' => $e->getMessage()]);

            return false;
        } catch (\Http\Client\Exception $e) {
            Log::error('Failed to revoke search key.', ['key' => $key, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
