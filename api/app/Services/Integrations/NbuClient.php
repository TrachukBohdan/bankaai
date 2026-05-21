<?php

declare(strict_types=1);

namespace App\Services\Integrations;

use App\Contracts\RateProvider;
use App\DTO\RateSnapshot;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\Http\HttpJsonClient;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Log;

/**
 * Adapter for the National Bank of Ukraine statistical directory API.
 *
 * Response shape (verified live, 2026):
 *   [{"r030":840,"txt":"Долар США","rate":44.153,"cc":"USD","exchangedate":"20.05.2026","special":"N"}]
 *
 * NBU does not distinguish buy/sell — it publishes a single mid-market rate.
 * We populate both `buy` and `sell` with that single number; the database
 * carries `market = official` to make NBU rows distinguishable.
 */
final class NbuClient extends HttpJsonClient implements RateProvider
{
    public function source(): string
    {
        return ExchangeRate::SOURCE_NBU;
    }

    protected function baseUrl(): string
    {
        return 'https://bank.gov.ua/NBUStatService/v1/statdirectory/';
    }

    public function fetchRatesFor(Currency $currency): array
    {
        $response = $this->client()->get('exchange', [
            'json' => '',
            'valcode' => $currency->code,
        ]);

        if (! $response->successful()) {
            Log::warning('NBU request failed', [
                'status' => $response->status(),
                'currency' => $currency->code,
            ]);

            return [];
        }

        $rows = $response->json();
        if (! is_array($rows)) {
            return [];
        }

        $tz = new DateTimeZone(config('app.timezone', 'UTC'));
        $now = new DateTimeImmutable('now', $tz);

        $snapshots = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $rate = isset($row['rate']) ? (float) $row['rate'] : null;
            if ($rate === null) {
                continue;
            }
            $rateAt = isset($row['exchangedate'])
                ? (DateTimeImmutable::createFromFormat('d.m.Y', (string) $row['exchangedate'], $tz) ?: $now)
                : $now;
            $rateAt = $rateAt->setTime(0, 0);

            $snapshots[] = new RateSnapshot(
                bankSlug: null,
                currencyCode: $currency->code,
                market: ExchangeRate::MARKET_OFFICIAL,
                buy: $rate,
                sell: $rate,
                rateAt: $rateAt,
                source: $this->source(),
            );
        }

        return $snapshots;
    }
}
