<?php

namespace ChrisReedIO\ScoutKeys;

use Laravel\Scout\Console\FlushCommand;
use Laravel\Scout\Console\ImportCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use ChrisReedIO\ScoutKeys\Commands\ScoutKeysCommand;
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
            ->hasCommand(ScoutKeysCommand::class);
    }

    public function packageBooted(): void
    {
        // Register the SecureMeilisearch Commands if configured to do so
        if (config('scout-keys.register_commands', true)) {
            $this->commands([
                FlushCommand::class,
                ImportCommand::class,
            ]);
        }
    }
}
