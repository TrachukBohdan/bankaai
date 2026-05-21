<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Jobs\SyncBanksJob;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            BankSeeder::class,
            ExchangeRateSeeder::class,
            BranchSeeder::class,
        ]);

        // Pull logo, phone, legal address, etc. from finance.ua so /api/banks is
        // immediately useful after migrate --seed (not only after the scheduler).
        SyncBanksJob::dispatchSync();

        // Demo account so the UI is immediately usable. Idempotent thanks to
        // updateOrCreate semantics inside firstOrCreate.
        User::firstOrCreate(
            ['email' => 'demo@bankaai.test'],
            [
                'name' => 'Demo User',
                'password' => 'password',
                'notifications_enabled' => true,
            ],
        );
    }
}
