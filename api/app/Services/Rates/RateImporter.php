<?php

declare(strict_types=1);

namespace App\Services\Rates;

use App\DTO\RateSnapshot;
use App\Events\RateImported;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SRP: this is the ONLY class that writes ExchangeRate rows.
 *
 * Lookup tables (currency code -> id, bank slug -> id) are materialised once
 * per import call to avoid N+1 queries. We accept both MinFin and finance.ua
 * slugs because the Bank model stores both.
 */
final class RateImporter
{
    /**
     * @param  iterable<RateSnapshot>  $snapshots
     * @return int number of rows written
     */
    public function import(iterable $snapshots): int
    {
        $currencyIds = Currency::pluck('id', 'code')->all();
        $banks = Bank::query()
            ->select(['id', 'slug', 'minfin_slug', 'finance_ua_slug'])
            ->get();

        $bankIdsBySlug = $this->buildBankSlugIndex($banks);

        $written = 0;
        foreach ($snapshots as $snapshot) {
            $currencyId = $currencyIds[$snapshot->currencyCode] ?? null;
            if ($currencyId === null) {
                continue;
            }

            $bankId = null;
            if ($snapshot->bankSlug !== null) {
                $bankId = $bankIdsBySlug[$snapshot->bankSlug] ?? null;
                if ($bankId === null) {
                    // Bank not in our hand-picked 5 — skip silently. This is
                    // expected for the MinFin "long tail" of banks.
                    continue;
                }
            }

            try {
                $rate = DB::transaction(function () use ($snapshot, $bankId, $currencyId): ExchangeRate {
                    return ExchangeRate::create([
                        'bank_id' => $bankId,
                        'currency_id' => $currencyId,
                        'market' => $snapshot->market,
                        'source' => $snapshot->source,
                        'buy' => $snapshot->buy,
                        'sell' => $snapshot->sell,
                        'rate_at' => Carbon::instance($snapshot->rateAt),
                        'fetched_at' => Carbon::now(),
                    ]);
                });

                event(new RateImported($rate));
                $written++;
            } catch (Throwable $e) {
                Log::warning('RateImporter::import row failed', [
                    'message' => $e->getMessage(),
                    'currency' => $snapshot->currencyCode,
                    'source' => $snapshot->source,
                    'bank' => $snapshot->bankSlug,
                ]);
            }
        }

        return $written;
    }

    /**
     * Build slug => bank_id with all known aliases (canonical, minfin, finance.ua).
     * If two banks accidentally share an alias (shouldn't happen given our seed),
     * the last one wins — that's acceptable for a manual seed list.
     *
     * @return array<string, int>
     */
    private function buildBankSlugIndex(iterable $banks): array
    {
        $index = [];
        foreach ($banks as $bank) {
            foreach ([$bank->slug, $bank->minfin_slug, $bank->finance_ua_slug] as $alias) {
                if (is_string($alias) && $alias !== '') {
                    $index[$alias] = (int) $bank->id;
                }
            }
        }

        return $index;
    }
}
