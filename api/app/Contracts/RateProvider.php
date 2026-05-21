<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\RateSnapshot;
use App\Models\Currency;

interface RateProvider
{
    /**
     * Fetch the latest exchange-rate snapshots for the given currency.
     *
     * @return list<RateSnapshot>
     */
    public function fetchRatesFor(Currency $currency): array;

    /** Stable identifier used in `exchange_rates.source` (e.g. "minfin", "nbu"). */
    public function source(): string;
}
