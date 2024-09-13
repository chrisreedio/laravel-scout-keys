<?php

use ChrisReedIO\ScoutKeys\Facades\ScoutKeys;
use ChrisReedIO\ScoutKeys\Http\Controllers\ShowSearchKeyController;
use Illuminate\Support\Facades\Route;

// Route::middleware('auth')
//     ->prefix('api/search')
//     ->as('api.search-keys.')
//     ->group(function () {
//         Route::get('key', ShowSearchKeyController::class)->name('show');
//     });

Route::middleware(ScoutKeys::getMiddleware())
    ->prefix('search')
    ->as('search-keys.')
    ->group(function () {
        Route::get('key', ShowSearchKeyController::class)->name('show');
    });
