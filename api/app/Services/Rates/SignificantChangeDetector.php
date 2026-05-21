<?php

declare(strict_types=1);

namespace App\Services\Rates;

use App\Models\ExchangeRate;
use App\Models\RateChange;
use Illuminate\Support\Carbon;

/**
 * SRP: compares a freshly-imported ExchangeRate against the previous reading
 * for the same (bank, currency, market, source) tuple. If either side moved
 * by more than the configured percentage threshold, persist a RateChange row.
 *
 * Returns the list of created RateChange rows so the listener can fan them
 * out into notifications.
 */
final class SignificantChangeDetector
{
    public function __construct(
        // The default threshold from task §4 is "e.g. 5%". Configurable via env
        // so we can tune it per environment without redeploying.
        private float $defaultThresholdPct = 5.0,
    ) {
        $envValue = config('services.rates.significant_threshold_pct');
        if (is_numeric($envValue)) {
            $this->defaultThresholdPct = (float) $envValue;
        }
    }

    /** @return list<RateChange> */
    public function detect(ExchangeRate $rate): array
    {
        // We only consider the previous reading older than the current one to
        // avoid race conditions when MinFin returns multiple identical timestamps
        // on the same poll.
        $previous = ExchangeRate::query()
            ->where('bank_id', $rate->bank_id)
            ->where('currency_id', $rate->currency_id)
            ->where('market', $rate->market)
            ->where('source', $rate->source)
            ->where('id', '!=', $rate->id)
            ->orderByDesc('rate_at')
            ->orderByDesc('id')
            ->first();

        if ($previous === null) {
            return [];
        }

        $created = [];
        foreach (['buy', 'sell'] as $side) {
            $row = $this->maybeCreate($rate, $previous, $side);
            if ($row !== null) {
                $created[] = $row;
            }
        }

        return $created;
    }

    private function maybeCreate(ExchangeRate $rate, ExchangeRate $previous, string $side): ?RateChange
    {
        $oldValue = (float) ($previous->{$side} ?? 0.0);
        $newValue = (float) ($rate->{$side} ?? 0.0);

        if ($oldValue <= 0.0 || $newValue <= 0.0) {
            return null;
        }

        $deltaPct = (($newValue - $oldValue) / $oldValue) * 100.0;
        if (abs($deltaPct) < $this->defaultThresholdPct) {
            return null;
        }

        return RateChange::create([
            'bank_id' => $rate->bank_id,
            'currency_id' => $rate->currency_id,
            'market' => $rate->market,
            'source' => $rate->source,
            'side' => $side,
            'previous_value' => $oldValue,
            'new_value' => $newValue,
            'delta_pct' => round($deltaPct, 4),
            'threshold_pct' => $this->defaultThresholdPct,
            'observed_at' => Carbon::instance($rate->rate_at),
        ]);
    }
}
