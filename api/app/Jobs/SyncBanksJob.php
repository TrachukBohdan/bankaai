<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\BankDirectory;
use App\Models\Bank;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Enriches our 5 hand-picked banks with directory metadata from finance.ua:
 * logo URL, legal address, phone, email, license info.
 *
 * We never INSERT new banks here — the seeded list is the source of truth for
 * which banks we expose. This keeps the surface area small (YAGNI) and avoids
 * polluting our UI with hundreds of banks that have no rates configured.
 */
final class SyncBanksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 120;

    public int $timeout = 60;

    public function handle(BankDirectory $directory): void
    {
        $banks = Bank::active()->whereNotNull('finance_ua_slug')->get();
        if ($banks->isEmpty()) {
            return;
        }

        $slugs = $banks->pluck('finance_ua_slug')->filter()->values()->all();
        $records = $directory->fetchBanks($slugs);

        $updated = 0;
        foreach ($banks as $bank) {
            $record = $records[$bank->finance_ua_slug] ?? null;
            if ($record === null) {
                continue;
            }
            $bank->fill([
                'legal_name' => $record->legalName ?? $bank->legal_name,
                'logo_url' => $record->logoUrl ?? $bank->logo_url,
                'website' => $record->website ?? $bank->website,
                'phone' => $record->phone ?? $bank->phone,
                'email' => $record->email ?? $bank->email,
                'legal_address' => $record->legalAddress ?? $bank->legal_address,
                'license_number' => $record->licenseNumber ?? $bank->license_number,
                'license_date' => $record->licenseDate
                    ? Carbon::instance($record->licenseDate)
                    : $bank->license_date,
                'last_synced_at' => Carbon::now(),
            ]);
            $bank->save();
            $updated++;
        }

        Log::info('SyncBanksJob done', ['updated' => $updated]);
    }
}
