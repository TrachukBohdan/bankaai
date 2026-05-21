<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Bank */
final class BankResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'slug'           => $this->slug,
            'name'           => $this->name,
            'legal_name'     => $this->legal_name,
            'description'    => $this->description,
            'logo_url'       => $this->logo_url,
            'website'        => $this->website ?: null,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'legal_address'  => $this->legal_address,
            'license_number' => $this->license_number,
            'license_date'   => $this->license_date?->toDateString(),
            'rating'         => $this->rating !== null ? (float) $this->rating : null,
            'rates'          => RateResource::collection($this->whenLoaded('currentRates')),
            'branches'       => BranchResource::collection($this->whenLoaded('branches')),
        ];
    }
}
