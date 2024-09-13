<?php

namespace ChrisReedIO\ScoutKeys;

use ChrisReedIO\ScoutKeys\Http\Controllers\ShowSearchKeyController;
use Closure;
use Illuminate\Support\Facades\Route;
use function call_user_func;
use function is_array;

class ScoutKeys
{
    /** @var array<string>|Closure */
    protected static array | Closure $middleware = ['web'];

    public static function middleware(string | array | Closure $middleware): void
    {
        if (! is_array($middleware) && ! $middleware instanceof Closure) {
            $middleware = [$middleware];
        }
        static::$middleware = $middleware;
    }

    public static function getMiddleware(): array
    {
        if (static::$middleware instanceof Closure) {
            return call_user_func(static::$middleware);
        }

        return static::$middleware;
    }

    public function keyRoute(?string $path = 'search/key'): \Illuminate\Routing\Route
    {
        return Route::middleware(ScoutKeys::getMiddleware())
            ->get($path, ShowSearchKeyController::class)
            ->name('search-keys.show');
    }
}
