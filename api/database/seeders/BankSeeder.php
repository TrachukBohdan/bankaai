<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

class BankSeeder extends Seeder
{
    public function run(): void
    {
        // Hand-picked 5 banks per task §1. Slugs verified live:
        //   - PrivatBank, Oschadbank, PUMB, Ukreximbank share the same slug
        //     on both MinFin and finance.ua.
        //   - Raiffeisen Bank Aval uses different slugs (MinFin: "aval",
        //     finance.ua: "raiffeisen-bank-aval"), so we record both.
        // The `slug` field is our internal/canonical identifier used in URLs.
        // SyncBanksJob will enrich logo_url / legal_address / phone / email
        // from finance.ua's organizationsList API.
        $banks = [
            [
                'slug' => 'privatbank',
                'minfin_slug' => 'privatbank',
                'finance_ua_slug' => 'privatbank',
                'name' => 'PrivatBank',
                'rating' => 4.8,
            ],
            [
                'slug' => 'oschadbank',
                'minfin_slug' => 'oschadbank',
                'finance_ua_slug' => 'oschadbank',
                'name' => 'Oschadbank',
                'rating' => 4.5,
            ],
            [
                'slug' => 'pumb',
                'minfin_slug' => 'pumb',
                'finance_ua_slug' => 'pumb',
                'name' => 'PUMB',
                'rating' => 4.6,
            ],
            [
                'slug' => 'raiffeisen',
                'minfin_slug' => 'aval',
                'finance_ua_slug' => 'raiffeisen-bank-aval',
                'name' => 'Raiffeisen Bank',
                'rating' => 4.7,
            ],
            [
                'slug' => 'ukreximbank',
                'minfin_slug' => 'ukreximbank',
                'finance_ua_slug' => 'ukreximbank',
                'name' => 'Ukreximbank',
                'rating' => 4.3,
            ],
        ];

        foreach ($banks as $bank) {
            Bank::updateOrCreate(
                ['slug' => $bank['slug']],
                $bank + ['is_active' => true],
            );
        }
    }
}
