<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Branches\BranchesJsonImporter;
use Illuminate\Database\Seeder;

/**
 * Branch list from database/data/branches.json (finance.ua snapshot, no HTTP).
 */
class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $importer = app(BranchesJsonImporter::class);
        $meta = $importer->meta();
        $rows = $importer->import(replaceExisting: true);

        $this->command?->info("BranchSeeder: imported {$rows} branches (JSON had {$meta['count']} rows).");
    }
}
