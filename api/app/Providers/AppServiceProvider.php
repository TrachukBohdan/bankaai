<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\BankDirectory;
use App\Contracts\BranchDirectory;
use App\Contracts\RateProvider;
use App\Events\RateImported;
use App\Listeners\DetectAndAnnounceChange;
use App\Services\Integrations\FinanceUaClient;
use App\Services\Integrations\MinFinClient;
use App\Services\Integrations\NbuClient;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Dependency inversion: callers depend on interfaces. Default `RateProvider`
        // binding is MinFinClient because it's the richer dataset; the NBU job
        // resolves its concrete client by class name (its source() differs).
        $this->app->bind(RateProvider::class, MinFinClient::class);
        $this->app->bind(BankDirectory::class, FinanceUaClient::class);
        $this->app->bind(BranchDirectory::class, FinanceUaClient::class);

        // FinanceUaClient must be a singleton because it implements two contracts
        // and we want a single HTTP factory instance to be reused.
        $this->app->singleton(FinanceUaClient::class);
        $this->app->singleton(MinFinClient::class);
        $this->app->singleton(NbuClient::class);
    }

    public function boot(): void
    {
        Event::listen(RateImported::class, DetectAndAnnounceChange::class);
    }
}
