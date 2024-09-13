<?php

namespace ChrisReedIO\ScoutKeys\Contracts;

use ChrisReedIO\ScoutKeys\Models\SearchKey;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Interface SearchUser
 *
 * @property-read SearchKey|null $searchKey
 */
interface SearchUser
{
    public function searchKeys(): HasMany;

    public function getSearchKeyAttribute(): ?SearchKey;

    public function generateSearchKey(): ?SearchKey;
}
