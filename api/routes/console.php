<?php

declare(strict_types=1);

use App\Jobs\SyncBanksJob;
use App\Jobs\SyncBranchesJob;
use App\Jobs\SyncMinFinRatesJob;
use App\Jobs\SyncNbuRatesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rates change every few minutes; keep cadence tight but with
// `withoutOverlapping` so a slow upstream cannot stack jobs.
Schedule::job(new SyncMinFinRatesJob)->everyFifteenMinutes()->withoutOverlapping();
Schedule::job(new SyncNbuRatesJob)->everyFifteenMinutes()->withoutOverlapping();

// Directory data changes maybe weekly; daily refresh is plenty.
Schedule::job(new SyncBanksJob)->dailyAt('02:00');
Schedule::job(new SyncBranchesJob)->dailyAt('02:15');
