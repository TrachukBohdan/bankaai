<?php

declare(strict_types=1);

namespace App\Services\Rates;

use App\Models\ExchangeRate;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * SRP: computes min/max/avg + daily series for the given filter window.
 *
 * Returns arrays of plain rows so the API Resource layer can shape them
 * without re-querying.
 */
final class RateStatistics
{
    /**
     * Aggregate stats across the period for the configured filters.
     *
     * @param  array{
     *     from: DateTimeInterface,
     *     to: DateTimeInterface,
     *     bank_ids?: list<int>,
     *     currency_ids?: list<int>,
     *     source?: string,
     *     market?: string,
     * }  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters): array
    {
        $query = $this->baseQuery($filters);

        $row = $query
            ->selectRaw('MIN(buy)  AS min_buy,  MAX(buy)  AS max_buy,  AVG(buy)  AS avg_buy')
            ->selectRaw('MIN(sell) AS min_sell, MAX(sell) AS max_sell, AVG(sell) AS avg_sell')
            ->selectRaw('COUNT(*) AS samples')
            ->first();

        return [
            'samples' => (int) ($row->samples ?? 0),
            'buy' => [
                'min' => $this->float($row?->min_buy),
                'max' => $this->float($row?->max_buy),
                'avg' => $this->float($row?->avg_buy),
            ],
            'sell' => [
                'min' => $this->float($row?->min_sell),
                'max' => $this->float($row?->max_sell),
                'avg' => $this->float($row?->avg_sell),
            ],
        ];
    }

    /**
     * Per-day series: average buy/sell for each calendar day in the window.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function dailySeries(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->selectRaw('DATE(rate_at) AS day')
            ->selectRaw('AVG(buy) AS avg_buy')
            ->selectRaw('AVG(sell) AS avg_sell')
            ->groupByRaw('DATE(rate_at)')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => (string) $row->day,
                'avg_buy' => $this->float($row->avg_buy),
                'avg_sell' => $this->float($row->avg_sell),
            ]);
    }

    /** @return Builder<ExchangeRate> */
    private function baseQuery(array $filters)
    {
        $from = Carbon::instance($filters['from']);
        $to = Carbon::instance($filters['to']);

        $query = ExchangeRate::query()
            ->whereBetween('rate_at', [$from, $to]);

        if (! empty($filters['bank_ids'])) {
            $query->whereIn('bank_id', $filters['bank_ids']);
        }
        if (! empty($filters['currency_ids'])) {
            $query->whereIn('currency_id', $filters['currency_ids']);
        }
        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        if (! empty($filters['market'])) {
            $query->where('market', $filters['market']);
        }

        return $query;
    }

    private function float(mixed $v): ?float
    {
        if ($v === null) {
            return null;
        }

        return round((float) $v, 4);
    }
}
