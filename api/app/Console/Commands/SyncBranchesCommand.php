<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncBranchesJob;
use Illuminate\Console\Command;

final class SyncBranchesCommand extends Command
{
    protected $signature = 'branches:sync {--sync : Run inline instead of queuing}';

    protected $description = 'Sync bank branches from finance.ua (task §2: periodic directory updates)';

    public function handle(): int
    {
        $this->info('Syncing branches from finance.ua…');

        if ($this->option('sync')) {
            SyncBranchesJob::dispatchSync();
        } else {
            SyncBranchesJob::dispatch();
            $this->line('Job queued (requires a running queue worker).');
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
