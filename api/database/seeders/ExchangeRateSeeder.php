<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Demo history for statistics (task §80): MinFin cash rates for the 5 tracked
 * banks and 5 foreign currencies over the last 3 months.
 */
class ExchangeRateSeeder extends Seeder
{
    /** @var array<string, array{buy: float, sell: float}> */
    private const BASE_RATES = [
        'USD' => ['buy' => 41.20, 'sell' => 42.10],
        'EUR' => ['buy' => 44.50, 'sell' => 45.60],
        'GBP' => ['buy' => 52.10, 'sell' => 53.40],
        'CHF' => ['buy' => 46.80, 'sell' => 47.90],
        'PLN' => ['buy' => 10.35, 'sell' => 10.65],
    ];

    /** Per-bank spread applied on top of the currency base (UAH). */
    private const BANK_OFFSET = [
        'privatbank' => ['buy' => 0.00, 'sell' => 0.00],
        'oschadbank' => ['buy' => -0.08, 'sell' => 0.06],
        'pumb' => ['buy' => 0.05, 'sell' => -0.04],
        'raiffeisen' => ['buy' => 0.10, 'sell' => 0.12],
        'ukreximbank' => ['buy' => -0.12, 'sell' => 0.08],
    ];

    public function run(): void
    {
        $banks = Bank::query()->whereIn('slug', array_keys(self::BANK_OFFSET))->get()->keyBy('slug');
        $currencies = Currency::query()
            ->whereIn('code', array_keys(self::BASE_RATES))
            ->get()
            ->keyBy('code');

        if ($banks->isEmpty() || $currencies->isEmpty()) {
            return;
        }

        $from = Carbon::now()->subMonths(3)->startOfDay();
        $to = Carbon::now()->endOfDay();

        ExchangeRate::query()
            ->where('source', ExchangeRate::SOURCE_MINFIN)
            ->where('market', ExchangeRate::MARKET_CASH)
            ->whereBetween('rate_at', [$from, $to])
            ->delete();

        $rows = [];
        $fetchedAt = Carbon::now();
        $day = $from->copy();

        while ($day->lte($to)) {
            $dayIndex = (int) $from->diffInDays($day);
            foreach ($banks as $slug => $bank) {
                $offset = self::BANK_OFFSET[$slug] ?? ['buy' => 0.0, 'sell' => 0.0];
                foreach ($currencies as $code => $currency) {
                    $base = self::BASE_RATES[$code];
                    $trend = sin($dayIndex / 9) * 0.25 + ($dayIndex % 17) * 0.01;
                    $noise = (crc32($slug.$code.$dayIndex) % 7) * 0.01;

                    $rows[] = [
                        'bank_id' => $bank->id,
                        'currency_id' => $currency->id,
                        'market' => ExchangeRate::MARKET_CASH,
                        'source' => ExchangeRate::SOURCE_MINFIN,
                        'buy' => round($base['buy'] + $offset['buy'] + $trend + $noise, 4),
                        'sell' => round($base['sell'] + $offset['sell'] + $trend + $noise + 0.15, 4),
                        'rate_at' => $day->copy()->setTime(12, 0),
                        'fetched_at' => $fetchedAt,
                        'created_at' => $fetchedAt,
                        'updated_at' => $fetchedAt,
                    ];
                }
            }
            $day->addDay();
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            ExchangeRate::insert($chunk);
        }
    }
}
