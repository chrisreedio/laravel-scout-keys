<?php

namespace ChrisReedIO\ScoutKeys\Traits;

use ChrisReedIO\ScoutKeys\Models\SearchKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * @mixin Model
 */
trait HasSearchKeys
{
    /**
     * Gets the user's search keys.
     */
    // public function searchKeys(): HasMany
    // {
    //     return $this->hasMany(SearchKey::class);
    // }
    public function searchKeys(): MorphMany
    {
        return $this->morphMany(SearchKey::class, 'keyable');
    }

    /**
     * @throws Throwable
     */
    public function getSearchKeyAttribute(): ?SearchKey
    {
        $activeKey = $this->searchKeys()->active()->first();
        if ($activeKey === null) {
            // We need to generate a search key for this user.
            return $this->generateSearchKey();
        }

        return $activeKey;
    }

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
}
