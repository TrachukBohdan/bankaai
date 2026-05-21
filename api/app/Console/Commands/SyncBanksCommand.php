<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncBanksJob;
use Illuminate\Console\Command;

final class SyncBanksCommand extends Command
{
    protected $signature = 'banks:sync {--sync : Run inline instead of queuing}';

    protected $description = 'Fetch bank directory metadata from finance.ua and update local records';

    public function handle(): int
    {
        $this->info('Syncing banks from finance.ua…');

        if ($this->option('sync')) {
            SyncBanksJob::dispatchSync();
        } else {
            SyncBanksJob::dispatch();
            $this->line('Job queued (requires a running queue worker).');
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
