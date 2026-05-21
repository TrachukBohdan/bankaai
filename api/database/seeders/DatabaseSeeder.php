<?php

declare(strict_types=1);

namespace Database\Seeders;

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
        ]);

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
