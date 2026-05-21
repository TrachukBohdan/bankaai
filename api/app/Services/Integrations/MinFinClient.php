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
use Throwable;

/**
 * Adapter for MinFin's per-currency bank-rates API.
 *
 * Verified live response shape (2026):
 *   {
 *     "data": [
 *       {
 *         "slug": "privatbank",
 *         "name_uk": "...",
 *         "logo": "/img/.../privatbank@2x.png",
 *         "commercial_placement": true,
 *         "cash": { "date": "ISO-8601", "bid": "43.8",  "ask": "44.4" },
 *         "card": { "date": "ISO-8601", "bid": "43.95", "ask": "44.4444" } | null
 *       }, ...
 *     ],
 *     "meta": { "page": 1, "cpp": 10, "total": 46, "next": 2 }
 *   }
 *
 * - `bid` = bank buys the foreign currency at this rate (= our `buy`)
 * - `ask` = bank sells the foreign currency at this rate (= our `sell`)
 * Paginated; we walk pages until `meta.next` is null.
 */
final class MinFinClient extends HttpJsonClient implements RateProvider
{
    public function source(): string
    {
        return ExchangeRate::SOURCE_MINFIN;
    }

    protected function baseUrl(): string
    {
        return 'https://minfin.com.ua/api/currency/';
    }

    public function fetchRatesFor(Currency $currency): array
    {
        $snapshots = [];
        $page = 1;
        $maxPages = 10; // safety net: ~250 rows max

        while ($page <= $maxPages) {
            $response = $this->client()->get('rates/banks/'.strtolower($currency->code), [
                'page' => $page,
            ]);

            if (! $response->successful()) {
                Log::warning('MinFin request failed', [
                    'status' => $response->status(),
                    'currency' => $currency->code,
                    'page' => $page,
                ]);
                break;
            }

            $payload = $response->json();
            $rows = $payload['data'] ?? null;
            if (! is_array($rows)) {
                break;
            }

            foreach ($rows as $row) {
                $snapshots = [
                    ...$snapshots,
                    ...$this->mapBankRow((array) $row, $currency->code),
                ];
            }

            $next = $payload['meta']['next'] ?? null;
            if ($next === null) {
                break;
            }
            $page = (int) $next;
        }

        return $snapshots;
    }

    /** @return list<RateSnapshot> */
    private function mapBankRow(array $row, string $currencyCode): array
    {
        $bankSlug = isset($row['slug']) ? (string) $row['slug'] : null;
        if ($bankSlug === null) {
            return [];
        }

        $out = [];
        foreach ([ExchangeRate::MARKET_CASH => 'cash', ExchangeRate::MARKET_CARD => 'card'] as $market => $key) {
            $block = $row[$key] ?? null;
            if (! is_array($block)) {
                continue;
            }
            $bid = isset($block['bid']) ? (float) $block['bid'] : null;
            $ask = isset($block['ask']) ? (float) $block['ask'] : null;

            // MinFin occasionally returns a literal "0" for missing card rates.
            // Treat 0/0 as "no data" so we don't pollute history with bogus rows.
            if (($bid === null || $bid <= 0.0) && ($ask === null || $ask <= 0.0)) {
                continue;
            }

            $rateAt = $this->parseDate($block['date'] ?? null);

            $out[] = new RateSnapshot(
                bankSlug: $bankSlug,
                currencyCode: $currencyCode,
                market: $market,
                buy: $bid > 0 ? $bid : null,
                sell: $ask > 0 ? $ask : null,
                rateAt: $rateAt,
                source: $this->source(),
            );
        }

        return $out;
    }

    private function parseDate(mixed $raw): DateTimeImmutable
    {
        $tz = new DateTimeZone(config('app.timezone', 'UTC'));
        if (! is_string($raw) || $raw === '') {
            return new DateTimeImmutable('now', $tz);
        }
        try {
            return (new DateTimeImmutable($raw))->setTimezone($tz);
        } catch (Throwable) {
            return new DateTimeImmutable('now', $tz);
        }
    }
}
