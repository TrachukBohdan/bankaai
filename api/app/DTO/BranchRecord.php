<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class BranchRecord
{
    public function __construct(
        public string $financeUaSlug,
        public ?string $externalId,
        public string $name,
        public ?string $city,
        public string $address,
        public ?string $phone,
        public float $lat,
        public float $lng,
        public bool $isPrimary,
    ) {}
}
