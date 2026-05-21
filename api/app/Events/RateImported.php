<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ExchangeRate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RateImported
{
    use Dispatchable, SerializesModels;

    public function __construct(public ExchangeRate $rate) {}
}
