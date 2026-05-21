<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ExchangeRate */
final class RateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'bank'     => $this->when(
                $this->relationLoaded('bank') && $this->bank !== null,
                fn () => ['slug' => $this->bank->slug, 'name' => $this->bank->name],
            ),
            'currency' => $this->when(
                $this->relationLoaded('currency'),
                fn () => ['code' => $this->currency?->code, 'name' => $this->currency?->name],
            ),
            'market'   => $this->market,
            'source'   => $this->source,
            'buy'      => $this->buy,
            'sell'     => $this->sell,
            'rate_at'  => $this->rate_at?->toIso8601String(),
        ];
    }
}
