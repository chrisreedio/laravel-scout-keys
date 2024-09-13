<?php

namespace ChrisReedIO\ScoutKeys\Traits;

use Illuminate\Support\Facades\Artisan;

trait SearchFlush
{
    /**
     * Flush the search index for the model.
     * ! Warning: This requires the scout:flush and scout:import commands to be available.
     * ! Warning: This will remove all records from the search index and re-import them. This can be slow for large datasets.
     */
    public static function flushSearchIndex(): void
    {
        Artisan::call('scout:flush', [
            'model' => self::class,
        ]);

        Artisan::call('scout:import', [
            'model' => self::class,
        ]);
    }
}
