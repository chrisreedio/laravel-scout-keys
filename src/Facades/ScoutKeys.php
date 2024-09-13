<?php

namespace ChrisReedIO\ScoutKeys\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ChrisReedIO\ScoutKeys\ScoutKeys
 */
class ScoutKeys extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ChrisReedIO\ScoutKeys\ScoutKeys::class;
    }
}
