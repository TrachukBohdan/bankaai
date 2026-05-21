<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\RateChange */
final class RateChangeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'bank'           => $this->when(
                $this->relationLoaded('bank'),
                fn () => $this->bank ? ['slug' => $this->bank->slug, 'name' => $this->bank->name] : null,
            ),
            'currency'       => $this->when(
                $this->relationLoaded('currency'),
                fn () => ['code' => $this->currency?->code, 'name' => $this->currency?->name],
            ),
            'market'         => $this->market,
            'source'         => $this->source,
            'side'           => $this->side,
            'previous_value' => $this->previous_value,
            'new_value'      => $this->new_value,
            'delta_pct'      => $this->delta_pct,
            'threshold_pct'  => $this->threshold_pct,
            'observed_at'    => $this->observed_at?->toIso8601String(),
        ];
    }
}
