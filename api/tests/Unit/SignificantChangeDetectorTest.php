<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Bank;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\Rates\SignificantChangeDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignificantChangeDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_does_not_flag_small_changes(): void
    {
        [$bank, $currency] = $this->scaffold();

        $previous = ExchangeRate::create([
            'bank_id' => $bank->id, 'currency_id' => $currency->id,
            'market' => 'cash', 'source' => 'minfin',
            'buy' => 40.0, 'sell' => 41.0,
            'rate_at' => now()->subHour(), 'fetched_at' => now()->subHour(),
        ]);
        $current = ExchangeRate::create([
            'bank_id' => $bank->id, 'currency_id' => $currency->id,
            'market' => 'cash', 'source' => 'minfin',
            'buy' => 40.5, 'sell' => 41.5,  // ~1.25% move, below 5% threshold
            'rate_at' => now(), 'fetched_at' => now(),
        ]);

        $detector = new SignificantChangeDetector(5.0);
        $changes = $detector->detect($current);

        $this->assertSame([], $changes);
    }

    public function test_flags_big_changes_on_both_sides(): void
    {
        [$bank, $currency] = $this->scaffold();

        ExchangeRate::create([
            'bank_id' => $bank->id, 'currency_id' => $currency->id,
            'market' => 'cash', 'source' => 'minfin',
            'buy' => 40.0, 'sell' => 41.0,
            'rate_at' => now()->subHour(), 'fetched_at' => now()->subHour(),
        ]);
        $current = ExchangeRate::create([
            'bank_id' => $bank->id, 'currency_id' => $currency->id,
            'market' => 'cash', 'source' => 'minfin',
            'buy' => 44.0, 'sell' => 45.5,  // +10% / +10.9%
            'rate_at' => now(), 'fetched_at' => now(),
        ]);

        $detector = new SignificantChangeDetector(5.0);
        $changes = $detector->detect($current);

        $this->assertCount(2, $changes);
        $this->assertGreaterThan(5.0, abs($changes[0]->delta_pct));
    }

    /**
     * @return array{0: Bank, 1: Currency}
     */
    private function scaffold(): array
    {
        $bank = Bank::create(['slug' => 'test', 'name' => 'Test Bank', 'is_active' => true]);
        $currency = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'is_active' => true, 'sort_order' => 1]);

        return [$bank, $currency];
    }
}
