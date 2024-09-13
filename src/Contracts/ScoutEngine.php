<?php

namespace ChrisReedIO\ScoutKeys\Contracts;

use ChrisReedIO\ScoutKeys\Models\SearchKey;

interface ScoutEngine
{
    public static function generateScopedKey(SearchKey $key): ?string;

    public static function revokeKey(SearchKey $key): bool;
}
