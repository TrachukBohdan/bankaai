<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Branch */
final class BranchResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'bank'        => $this->when(
                $this->relationLoaded('bank'),
                fn () => [
                    'slug' => $this->bank?->slug,
                    'name' => $this->bank?->name,
                ],
            ),
            'name'        => $this->name,
            'city'        => $this->city,
            'address'     => $this->address,
            'phone'       => $this->phone,
            'lat'         => $this->lat,
            'lng'         => $this->lng,
            'is_primary'  => $this->is_primary,
            'distance_m'  => isset($this->distance_m) ? round((float) $this->distance_m) : null,
        ];
    }
}
