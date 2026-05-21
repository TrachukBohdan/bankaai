<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RateImported;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\SignificantRateChange;
use App\Services\Rates\SignificantChangeDetector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Reacts to RateImported events: asks the detector if anything moved beyond
 * the threshold, then routes each created RateChange to subscribers.
 *
 * Marked ShouldQueue so import throughput is not bound by SMTP latency.
 */
final class DetectAndAnnounceChange implements ShouldQueue
{
    public function __construct(private SignificantChangeDetector $detector) {}

    public function handle(RateImported $event): void
    {
        $changes = $this->detector->detect($event->rate);
        if ($changes === []) {
            return;
        }

        foreach ($changes as $change) {
            $recipients = $this->resolveRecipients($change->bank_id, $change->currency_id);
            if ($recipients->isEmpty()) {
                continue;
            }
            foreach ($recipients as $user) {
                $user->notify(new SignificantRateChange($change));
            }
        }

        Log::info('Rate-change notifications dispatched', [
            'changes' => count($changes),
        ]);
    }

    /** @return Collection<int, User> */
    private function resolveRecipients(?int $bankId, int $currencyId)
    {
        // Pull every subscription that matches this (bank, currency) tuple
        // (NULLs mean "any"), then filter by the user's global opt-in flag.
        return Subscription::query()
            ->with('user')
            ->where(function ($q) use ($bankId) {
                $q->whereNull('bank_id');
                if ($bankId !== null) {
                    $q->orWhere('bank_id', $bankId);
                }
            })
            ->where(function ($q) use ($currencyId) {
                $q->whereNull('currency_id')->orWhere('currency_id', $currencyId);
            })
            ->get()
            ->map(fn (Subscription $s) => $s->user)
            ->filter(fn (?User $u) => $u !== null && $u->notifications_enabled)
            ->unique('id')
            ->values();
    }
}
