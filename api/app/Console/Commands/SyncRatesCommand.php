<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncMinFinRatesJob;
use App\Jobs\SyncNbuRatesJob;
use Illuminate\Console\Command;

final class SyncRatesCommand extends Command
{
    protected $signature = 'rates:sync {--sync : Run jobs inline instead of queuing}';

    protected $description = 'Sync MinFin and NBU exchange rates (task §2: periodic updates)';

    public function handle(): int
    {
        $this->info('Syncing exchange rates from MinFin and NBU…');

        if ($this->option('sync')) {
            SyncMinFinRatesJob::dispatchSync();
            SyncNbuRatesJob::dispatchSync();
        } else {
            SyncMinFinRatesJob::dispatch();
            SyncNbuRatesJob::dispatch();
            $this->line('Jobs queued (requires a running queue worker).');
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
