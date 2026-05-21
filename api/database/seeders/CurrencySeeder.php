<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        // Task §1 requires this exact set. UAH is included as our reporting/base
        // currency so foreign-key targets exist if we ever need to relate rates
        // to UAH explicitly.
        $rows = [
            ['code' => 'USD', 'iso_numeric' => 840, 'name' => 'US Dollar',     'symbol' => '$',  'sort_order' => 10],
            ['code' => 'EUR', 'iso_numeric' => 978, 'name' => 'Euro',          'symbol' => '€',  'sort_order' => 20],
            ['code' => 'GBP', 'iso_numeric' => 826, 'name' => 'Pound Sterling', 'symbol' => '£',  'sort_order' => 30],
            ['code' => 'CHF', 'iso_numeric' => 756, 'name' => 'Swiss Franc',   'symbol' => '₣',  'sort_order' => 40],
            ['code' => 'PLN', 'iso_numeric' => 985, 'name' => 'Polish Złoty',  'symbol' => 'zł', 'sort_order' => 50],
            ['code' => 'UAH', 'iso_numeric' => 980, 'name' => 'Ukrainian Hryvnia', 'symbol' => '₴', 'sort_order' => 0],
        ];

        foreach ($rows as $row) {
            Currency::updateOrCreate(['code' => $row['code']], $row + ['is_active' => true]);
        }
    }
}
