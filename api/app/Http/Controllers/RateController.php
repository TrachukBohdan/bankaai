<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\HistoryRequest;
use App\Http\Requests\RateFilterRequest;
use App\Http\Requests\StatisticsRequest;
use App\Http\Resources\RateChangeResource;
use App\Http\Resources\RateResource;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\RateChange;
use App\Services\Rates\CurrentRatesQuery;
use App\Services\Rates\RateStatistics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

final class RateController extends Controller
{
    public function __construct(
        private CurrentRatesQuery $currentRates,
        private RateStatistics $statistics,
    ) {}

    public function index(RateFilterRequest $request): AnonymousResourceCollection
    {
        $filters = $request->filters();
        $filters['source'] ??= ExchangeRate::SOURCE_MINFIN;
        $filters['market']  ??= ExchangeRate::MARKET_CASH;

        return RateResource::collection($this->currentRates->get($filters));
    }

    public function nbu(RateFilterRequest $request): JsonResponse
    {
        $filters = $request->filters();
        $filters['source'] = ExchangeRate::SOURCE_NBU;
        $filters['market'] = ExchangeRate::MARKET_OFFICIAL;

        $nbuRates = $this->currentRates->get($filters);

        // Average across our tracked banks (MinFin cash market).
        $bankFilters = $filters;
        unset($bankFilters['source'], $bankFilters['market']);
        $bankFilters['source'] = ExchangeRate::SOURCE_MINFIN;
        $bankFilters['market'] = ExchangeRate::MARKET_CASH;

        $bankRates = $this->currentRates->get($bankFilters);

        $averages = [];
        foreach ($bankRates->groupBy('currency_id') as $currencyId => $rows) {
            $buyValues  = $rows->pluck('buy')->filter(fn ($v) => $v > 0);
            $sellValues = $rows->pluck('sell')->filter(fn ($v) => $v > 0);
            $averages[$currencyId] = [
                'currency' => $rows->first()?->currency,
                'avg_buy'  => $buyValues->isNotEmpty()  ? round($buyValues->avg(), 4)  : null,
                'avg_sell' => $sellValues->isNotEmpty() ? round($sellValues->avg(), 4) : null,
                'banks'    => $rows->count(),
            ];
        }

        return response()->json([
            'nbu'      => RateResource::collection($nbuRates),
            'averages' => array_values($averages),
        ]);
    }

    public function history(HistoryRequest $request): AnonymousResourceCollection
    {
        $query = RateChange::query()
            ->with(['bank', 'currency'])
            ->whereBetween('observed_at', [$request->fromDate(), $request->toDate()])
            ->orderByDesc('observed_at');

        if ($request->filled('bank')) {
            $bankId = Bank::where('slug', $request->input('bank'))->value('id');
            $query->where('bank_id', $bankId);
        }
        if ($request->filled('currency')) {
            $currencyId = Currency::where('code', strtoupper((string) $request->input('currency')))->value('id');
            $query->where('currency_id', $currencyId);
        }

        return RateChangeResource::collection($query->paginate(50));
    }

    public function statistics(StatisticsRequest $request): JsonResponse
    {
        $filters = $request->filters();

        // Map slug/code filters to ids for RateStatistics.
        $statsFilters = [
            'from' => $filters['from'],
            'to'   => $filters['to'],
        ];
        if (! empty($filters['bank_slugs'])) {
            $statsFilters['bank_ids'] = Bank::whereIn('slug', $filters['bank_slugs'])->pluck('id')->all();
        }
        if (! empty($filters['currency_codes'])) {
            $statsFilters['currency_ids'] = Currency::whereIn('code', $filters['currency_codes'])->pluck('id')->all();
        }
        if (! empty($filters['source'])) {
            $statsFilters['source'] = $filters['source'];
            $statsFilters['market'] = $filters['market']
                ?? ($filters['source'] === ExchangeRate::SOURCE_NBU
                    ? ExchangeRate::MARKET_OFFICIAL
                    : ExchangeRate::MARKET_CASH);
        } else {
            $statsFilters['source'] = ExchangeRate::SOURCE_MINFIN;
            $statsFilters['market']  = $filters['market'] ?? ExchangeRate::MARKET_CASH;
        }

        return response()->json([
            'period' => [
                'from' => Carbon::instance($statsFilters['from'])->toDateString(),
                'to'   => Carbon::instance($statsFilters['to'])->toDateString(),
            ],
            'summary' => $this->statistics->summary($statsFilters),
            'series'  => $this->statistics->dailySeries($statsFilters),
        ]);
    }
}
