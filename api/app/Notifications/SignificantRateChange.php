<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\RateChange;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SignificantRateChange extends Notification
{
    use Queueable;

    public function __construct(public RateChange $change) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $bankName = $this->change->bank?->name ?? 'NBU';
        $currency = $this->change->currency?->code ?? '???';
        $direction = $this->change->delta_pct >= 0 ? '+' : '';
        $delta = $direction.number_format($this->change->delta_pct, 2).'%';

        return (new MailMessage)
            ->subject("BankaAi alert — {$bankName} {$currency} {$delta}")
            ->greeting("Hi {$notifiable->name},")
            ->line("{$bankName} just moved its {$currency} {$this->change->side} rate by {$delta}.")
            ->line("Previous: {$this->change->previous_value}")
            ->line("Current: {$this->change->new_value}")
            ->line("Threshold: {$this->change->threshold_pct}%")
            ->action('Open BankaAi', url('/'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'rate_change_id' => $this->change->id,
            'bank_id' => $this->change->bank_id,
            'currency_id' => $this->change->currency_id,
            'side' => $this->change->side,
            'delta_pct' => $this->change->delta_pct,
            'previous_value' => $this->change->previous_value,
            'new_value' => $this->change->new_value,
            'observed_at' => $this->change->observed_at?->toIso8601String(),
        ];
    }
}
