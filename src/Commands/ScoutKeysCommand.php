<?php

namespace ChrisReedIO\ScoutKeys\Commands;

use Illuminate\Console\Command;

class ScoutKeysCommand extends Command
{
    public $signature = 'laravel-scout-keys';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
