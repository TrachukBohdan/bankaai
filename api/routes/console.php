<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Periodic upstream sync (task §2)
|--------------------------------------------------------------------------
|
| Jobs implement ShouldQueue — the scheduler only dispatches them; the
| `queue` container (or `php artisan queue:work`) executes the HTTP calls.
|
| Dev/prod: run `scheduler` + `queue` services (see docker-compose*.yml).
| Manual:   php artisan rates:sync|banks:sync|branches:sync [--sync]
|
*/

// Exchange rates: MinFin + NBU, every 15 minutes (task §2).
Schedule::command('rates:sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping(30)
    ->name('sync-exchange-rates')
    ->onOneServer();

// Bank directory metadata from finance.ua — daily (task §1–2).
Schedule::command('banks:sync')
    ->dailyAt('02:00')
    ->withoutOverlapping(60)
    ->name('sync-banks')
    ->onOneServer();

// Branch list per bank — daily, after bank metadata (task §2).
Schedule::command('branches:sync')
    ->dailyAt('02:15')
    ->withoutOverlapping(120)
    ->name('sync-branches')
    ->onOneServer();
