<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\BankResource;
use App\Models\Bank;
use App\Models\ExchangeRate;
use App\Services\Rates\CurrentRatesQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class BankController extends Controller
{
    public function __construct(private CurrentRatesQuery $currentRates) {}

    public function index(): AnonymousResourceCollection
    {
        return BankResource::collection(
            Bank::active()->orderByDesc('rating')->get(),
        );
    }

    public function show(string $slug): BankResource
    {
        $bank = Bank::where('slug', $slug)->firstOrFail();

        $rates = $this->currentRates->get([
            'bank_slugs' => [$bank->slug],
            'source'     => ExchangeRate::SOURCE_MINFIN,
            'market'     => ExchangeRate::MARKET_CASH,
        ]);

        $bank->setRelation('currentRates', $rates);
        $bank->load(['branches' => fn ($q) => $q->limit(50)]);

        return BankResource::make($bank);
    }
}
