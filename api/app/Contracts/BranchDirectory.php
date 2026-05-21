<?php

declare(strict_types=1);

namespace App\Contracts;

use App\DTO\BranchRecord;

interface BranchDirectory
{
    /**
     * Fetch all branches for the given finance.ua bank slug.
     *
     * @return list<BranchRecord>
     */
    public function fetchBranches(string $financeUaSlug): array;
}
