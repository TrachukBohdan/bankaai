<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Currency;
use App\Services\Integrations\NbuClient;
use App\Services\Rates\RateImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SyncNbuRatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $timeout = 90;

    public function handle(NbuClient $client, RateImporter $importer): void
    {
        $currencies = Currency::active()->where('code', '!=', 'UAH')->get();
        $total = 0;

        foreach ($currencies as $currency) {
            $snapshots = $client->fetchRatesFor($currency);
            $total += $importer->import($snapshots);
        }

        Log::info('SyncNbuRatesJob done', ['rows' => $total]);
    }
}
