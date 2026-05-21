<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RatesEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);
        $this->seed(\Database\Seeders\BankSeeder::class);

        $usd = Currency::where('code', 'USD')->firstOrFail();
        $bank = Bank::where('slug', 'privatbank')->firstOrFail();

        ExchangeRate::create([
            'bank_id' => $bank->id, 'currency_id' => $usd->id,
            'market' => 'cash', 'source' => 'minfin',
            'buy' => 43.8, 'sell' => 44.4,
            'rate_at' => Carbon::now()->subHour(), 'fetched_at' => Carbon::now(),
        ]);
        ExchangeRate::create([
            'bank_id' => null, 'currency_id' => $usd->id,
            'market' => 'official', 'source' => 'nbu',
            'buy' => 44.15, 'sell' => 44.15,
            'rate_at' => Carbon::now()->subHour(), 'fetched_at' => Carbon::now(),
        ]);
    }

    public function test_currencies_endpoint_returns_seeded_list(): void
    {
        $this->getJson('/api/currencies')
            ->assertOk()
            ->assertJsonStructure(['data' => [['code', 'name']]]);
    }

    public function test_banks_endpoint_lists_active_banks(): void
    {
        $this->getJson('/api/banks')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_rates_endpoint_filters_by_currency_slug(): void
    {
        $this->getJson('/api/rates?currencies[]=USD')
            ->assertOk()
            ->assertJsonFragment(['code' => 'USD']);
    }

    public function test_rates_nbu_endpoint_returns_nbu_and_averages(): void
    {
        $this->getJson('/api/rates/nbu')
            ->assertOk()
            ->assertJsonStructure([
                'nbu' => [['source', 'buy']],
                'averages' => [['currency', 'avg_buy', 'avg_sell', 'banks']],
            ]);
    }
}
