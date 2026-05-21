<?php

declare(strict_types=1);

namespace App\DTO;

use DateTimeImmutable;

final readonly class BankRecord
{
    public function __construct(
        public string $financeUaSlug,
        public string $name,
        public ?string $legalName,
        public ?string $logoUrl,
        public ?string $website,
        public ?string $phone,
        public ?string $email,
        public ?string $legalAddress,
        public ?string $licenseNumber,
        public ?DateTimeImmutable $licenseDate,
    ) {}
}
