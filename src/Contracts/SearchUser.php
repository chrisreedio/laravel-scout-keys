<?php

namespace ChrisReedIO\ScoutKeys\Contracts;

use ChrisReedIO\ScoutKeys\Models\SearchKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Interface SearchUser
 *
 * @mixin Model
 *
 * @property-read SearchKey|null $searchKey
 */
interface SearchUser
{
    // public function searchKeys(): HasMany;
    public function searchKeys(): MorphMany;

    public function searchKey(): MorphOne;

    // public function getSearchKeyAttribute(): ?SearchKey;

    public function generateSearchKey(): ?SearchKey;

    public function getSearchRules(): array;
}
