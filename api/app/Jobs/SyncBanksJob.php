<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\BankDirectory;
use App\DTO\BankRecord;
use App\Models\Bank;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Enriches seeded banks with directory metadata from finance.ua organizationsList.
 *
 * The seeder only stores slugs and a display name; this job fills logo, contacts,
 * legal info, and (when available) rating from the upstream API.
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
        $missing = [];

        foreach ($banks as $bank) {
            $record = $records[$bank->finance_ua_slug] ?? null;
            if ($record === null) {
                $missing[] = $bank->finance_ua_slug;
                continue;
            }

            $bank->fill($this->mapRecordToBank($bank, $record));
            $bank->last_synced_at = Carbon::now();
            $bank->save();
            $updated++;
        }

        if ($missing !== []) {
            Log::warning('SyncBanksJob: finance.ua returned no data for slugs', [
                'slugs' => $missing,
            ]);
        }

        Log::info('SyncBanksJob done', ['updated' => $updated]);
    }

    /** @return array<string, mixed> */
    private function mapRecordToBank(Bank $bank, BankRecord $record): array
    {
        return [
            'name'           => $record->name,
            'legal_name'     => $record->legalName ?? $bank->legal_name,
            'logo_url'       => $record->logoUrl ?? $bank->logo_url,
            'website'        => $record->website ?? $bank->website,
            'phone'          => $record->phone ?? $bank->phone,
            'email'          => $record->email ?? $bank->email,
            'legal_address'  => $record->legalAddress ?? $bank->legal_address,
            'license_number' => $record->licenseNumber ?? $bank->license_number,
            'license_date'   => $record->licenseDate
                ? Carbon::instance($record->licenseDate)
                : $bank->license_date,
            // Prefer upstream rating when finance.ua publishes one; keep seed value otherwise.
            'rating'         => $record->rating ?? $bank->rating,
        ];
    }
}
