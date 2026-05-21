<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Branch;
use App\Services\Branches\BranchesJsonImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchesJsonImporterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\BankSeeder::class);
    }

    public function test_imports_branches_from_json(): void
    {
        $path = database_path('data/branches.json');
        $this->assertFileExists($path);

        $importer = app(BranchesJsonImporter::class, ['dataPath' => $path]);
        $this->assertGreaterThan(1000, $importer->meta()['count']);

        $written = $importer->import(replaceExisting: true);
        $this->assertGreaterThan(1000, $written);

        $privat = Bank::where('slug', 'privatbank')->firstOrFail();
        $this->assertGreaterThan(
            100,
            Branch::where('bank_id', $privat->id)->count(),
        );
    }
}
