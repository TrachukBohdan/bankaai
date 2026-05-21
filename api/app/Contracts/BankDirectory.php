<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\BankRecord;

interface BankDirectory
{
    /**
     * Fetch directory metadata for the given finance.ua slugs.
     *
     * @param  list<string>  $slugs
     * @return array<string, BankRecord> keyed by finance.ua slug
     */
    public function fetchBanks(array $slugs): array;
}
