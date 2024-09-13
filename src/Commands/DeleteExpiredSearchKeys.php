<?php

namespace ChrisReedIO\ScoutKeys\Commands;

use ChrisReedIO\ScoutKeys\Models\SearchKey;
use Illuminate\Console\Command;

class DeleteExpiredSearchKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:keys:expired {--force : Force deletion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired search keys';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $keys = collect();
        if (! $this->option('force')) {
            $keys = SearchKey::expired()->get();
        } else {
            $keys = SearchKey::all();
        }

        if ($keys->count() == 0) {
            $this->comment('No expired search keys to revoke/delete.');

            return self::SUCCESS;
        }

        $keys->each(function (SearchKey $key) {
            // $this->comment('Deleting expired search key '.$key->id);
            if ($key->revoke()) {
                $this->info('Expired search key '.$key->id.' revoked & deleted.');
            } else {
                $this->error('Failed to delete expired search key '.$key->id);
            }
        });

        return self::SUCCESS;
    }
}
