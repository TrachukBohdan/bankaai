<?php

declare(strict_types=1);

namespace App\DTO;

use DateTimeImmutable;

/**
 * Immutable value object emitted by RateProvider implementations.
 *
 * Keeps the integration layer decoupled from Eloquent models: providers fetch,
 * parse and return RateSnapshot lists; the RateImporter is the only thing that
 * touches the DB.
 */
final readonly class RateSnapshot
{
    public function __construct(
        /** Slug used to look up the Bank row; NULL for NBU. */
        public ?string $bankSlug,
        public string $currencyCode,
        /** "cash" | "card" | "official" */
        public string $market,
        public ?float $buy,
        public ?float $sell,
        public DateTimeImmutable $rateAt,
        public string $source,
    ) {}
}
