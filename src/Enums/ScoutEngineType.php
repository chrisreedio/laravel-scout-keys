<?php

namespace ChrisReedIO\ScoutKeys\Enums;

use ChrisReedIO\ScoutKeys\Classes\Adapters\MeilisearchAdapter;
use ChrisReedIO\ScoutKeys\Classes\Adapters\TypesenseAdapter;

enum ScoutEngineType: string
{
    case Meilisearch = 'meilisearch';
    case Typesense = 'typesense';

    // case Algolia = 'algolia';

    public static function detect(): ?self
    {
        return match (config('scout.driver')) {
            'meilisearch' => self::Meilisearch,
            'typesense' => self::Typesense,
            // 'algolia' => self::Algolia,
            default => null,
        };
    }

    public function getAdapter(): string
    {
        return match ($this) {
            self::Meilisearch => MeilisearchAdapter::class,
            self::Typesense => TypesenseAdapter::class,
            // self::Algolia => 'AlgoliaAdapter',
        };
    }
}
