<?php

namespace ChrisReedIO\ScoutKeys\Traits;

use ChrisReedIO\ScoutKeys\Models\SearchKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * @mixin Model
 */
trait HasSearchKeys
{
    public function searchKeys(): MorphMany
    {
        return $this->morphMany(SearchKey::class, 'keyable');
    }

    public function searchKey(): MorphOne
    {
        return $this->morphOne(SearchKey::class, 'keyable')->latestOfMany();
    }

    // /**
    //  * @throws Throwable
    //  */
    // public function getSearchKeyAttribute(): ?SearchKey
    // {
    //     $activeKey = $this->searchKeys()->active()->first();
    //     if ($activeKey === null) {
    //         // We need to generate a search key for this user.
    //         return $this->generateSearchKey();
    //     }
    //
    //     return $activeKey;
    // }

    /**
     * @throws Throwable
     */
    public function generateSearchKey(): ?SearchKey
    {
        return DB::transaction(function () {
            // Create a new search key for the user.
            /** @var SearchKey $key */
            $key = $this->searchKeys()->create();
            $key->refresh();

            if (! $key->request()) {
                return null;
            }

            return $key;
        });
    }

    public function getSearchRules(): array
    {
        return [
            // Extend this method to return the rules for the search key
        ];
    }
}
