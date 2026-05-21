<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Subscription */
final class SubscriptionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'bank'           => $this->when(
                $this->relationLoaded('bank') && $this->bank !== null,
                fn () => ['slug' => $this->bank->slug, 'name' => $this->bank->name],
            ),
            'currency'       => $this->when(
                $this->relationLoaded('currency') && $this->currency !== null,
                fn () => ['code' => $this->currency->code, 'name' => $this->currency->name],
            ),
            'threshold_pct'  => $this->threshold_pct,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
