<?php

namespace ChrisReedIO\ScoutKeys;

use ChrisReedIO\ScoutKeys\Commands\DeleteExpiredSearchKeys;
use ChrisReedIO\ScoutKeys\Commands\RevokeUserSearchKeys;
use ChrisReedIO\ScoutKeys\Commands\ScoutKeysCommand;
use Laravel\Scout\Console\FlushCommand;
use Laravel\Scout\Console\ImportCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

use function config;

class ScoutKeysServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-scout-keys')
            ->hasConfigFile()
            // ->hasViews()
            // ->hasRoutes('../routes/web')
            ->hasMigration('create_search_keys_table')
            ->hasCommands([
                // ScoutKeysCommand::class,
                DeleteExpiredSearchKeys::class,
                RevokeUserSearchKeys::class,
            ]);
    }

    public function packageBooted(): void
    {
        // Register the Internal Commands if configured to do so
        if (config('scout-keys.register_commands', true)) {
            $this->commands([
                FlushCommand::class,
                ImportCommand::class,
            ]);
        }
    }
}
