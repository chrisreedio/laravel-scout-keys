<?php

namespace ChrisReedIO\ScoutKeys\Commands;

use ChrisReedIO\ScoutKeys\Models\SearchKey;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RevokeUserSearchKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scout:keys:revoke {key_id? : The key id to revoke}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke a (or all) search key(s) for a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $keyId = $this->argument('key_id');

        /** @var Collection<SearchKey> $keys */
        $keys = is_null($keyId) ? SearchKey::all() : SearchKey::where('id', $keyId)->get();

        if ($keys->count() == 0) {
            $this->warn('No keys to revoke.');

            return self::SUCCESS;
        }

        $keys->each(function ($key) {
            // $this->info('Revoking search key ' . $key->id);
            if ($key->revoke()) {
                $this->info('Search key ' . $key->id . ' revoked.');
            } else {
                $this->error('Failed to revoke search key ' . $key->id);
            }
        });

        return self::SUCCESS;
    }
}
