<?php

declare(strict_types=1);

namespace App\Services\Rates;

use App\Models\Bank;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Collection;

/**
 * Returns the most recent ExchangeRate per (bank_id, currency_id, market, source).
 *
 * At our scale (5 banks × 5 currencies × 2 markets) an in-memory de-dupe after
 * ordering by id DESC is simpler and fast enough than a correlated subquery.
 */
final class CurrentRatesQuery
{
    /**
     * @param  array{
     *     bank_ids?: list<int>,
     *     bank_slugs?: list<string>,
     *     currency_ids?: list<int>,
     *     currency_codes?: list<string>,
     *     source?: string,
     *     market?: string,
     * }  $filters
     * @return Collection<int, ExchangeRate>
     */
    public function get(array $filters = []): Collection
    {
        $query = ExchangeRate::query()
            ->with(['bank', 'currency'])
            ->orderByDesc('id');

        if (! empty($filters['bank_ids'])) {
            $query->whereIn('bank_id', $filters['bank_ids']);
        }
        if (! empty($filters['bank_slugs'])) {
            $ids = Bank::whereIn('slug', $filters['bank_slugs'])->pluck('id');
            $query->whereIn('bank_id', $ids);
        }
        if (! empty($filters['currency_ids'])) {
            $query->whereIn('currency_id', $filters['currency_ids']);
        }
        if (! empty($filters['currency_codes'])) {
            $ids = Currency::whereIn('code', $filters['currency_codes'])->pluck('id');
            $query->whereIn('currency_id', $ids);
        }
        if (! empty($filters['source'])) {
            $query->where('source', $filters['source']);
        }
        if (! empty($filters['market'])) {
            $query->where('market', $filters['market']);
        }

        return $query->get()->unique(
            fn (ExchangeRate $r) => implode('-', [
                $r->bank_id ?? 'nbu',
                $r->currency_id,
                $r->market,
                $r->source,
            ]),
        )->values();
    }
}
