<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Branches\BranchesJsonImporter;
use Illuminate\Console\Command;

final class ImportBranchesJsonCommand extends Command
{
    protected $signature = 'branches:import-json {path? : Path to JSON (default: database/data/branches.json)}';

    protected $description = 'Import branches from a JSON file (no HTTP)';

    public function handle(): int
    {
        $path = (string) ($this->argument('path') ?? database_path('data/branches.json'));
        $importer = app(BranchesJsonImporter::class, ['dataPath' => $path]);

        $this->info(sprintf(
            'Importing from %s (%d rows in file)…',
            $path,
            $importer->meta()['count'],
        ));

        $rows = $importer->import(replaceExisting: true);
        $this->info("Imported {$rows} branches.");

        return self::SUCCESS;
    }
}
