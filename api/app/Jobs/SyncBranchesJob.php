<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\BranchDirectory;
use App\Models\Bank;
use App\Models\Branch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sync branches for each active bank from finance.ua. Uses a transactional
 * "wipe-and-replace" strategy per bank because:
 *   - finance.ua's branch IDs are stable per (city, slot) but not strictly
 *     unique across deployments, so a "diff" approach is more complex than
 *     it's worth for an 8-hour scope (YAGNI).
 *   - Branches change rarely; wholesale replacement is acceptable.
 */
final class SyncBranchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 180;

    public int $timeout = 180;

    public function handle(BranchDirectory $directory): void
    {
        $banks = Bank::active()->whereNotNull('finance_ua_slug')->get();
        $total = 0;

        foreach ($banks as $bank) {
            $records = $directory->fetchBranches((string) $bank->finance_ua_slug);
            if ($records === []) {
                Log::info('No branches returned for bank', ['bank' => $bank->slug]);

                continue;
            }

            DB::transaction(function () use ($bank, $records, &$total): void {
                Branch::where('bank_id', $bank->id)->delete();
                foreach ($records as $r) {
                    Branch::create([
                        'bank_id' => $bank->id,
                        'external_id' => $r->externalId,
                        'name' => $r->name,
                        'city' => $r->city,
                        'address' => $r->address,
                        'phone' => $r->phone,
                        'lat' => $r->lat,
                        'lng' => $r->lng,
                        'is_primary' => $r->isPrimary,
                        'last_synced_at' => Carbon::now(),
                    ]);
                    $total++;
                }
            });
        }

        Log::info('SyncBranchesJob done', ['rows' => $total]);
    }
}
