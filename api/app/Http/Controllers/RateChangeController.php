<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StatisticsRequest;
use App\Http\Resources\RateChangeResource;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\RateChange;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class RateChangeController extends Controller
{
    /**
     * History of significant rate changes for the requested window.
     * Reuses StatisticsRequest because it accepts the same filters.
     */
    public function index(StatisticsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $from = isset($filters['from'])
            ? Carbon::parse($filters['from'])->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $to = isset($filters['to'])
            ? Carbon::parse($filters['to'])->endOfDay()
            : Carbon::now()->endOfDay();

        $bankIds = ! empty($filters['banks'])
            ? Bank::whereIn('slug', $filters['banks'])->pluck('id')->all()
            : [];
        $currencyIds = ! empty($filters['currencies'])
            ? Currency::whereIn('code', $filters['currencies'])->pluck('id')->all()
            : [];

        $rows = RateChange::query()
            ->with(['bank', 'currency'])
            ->whereBetween('observed_at', [$from, $to])
            ->when($bankIds, fn ($q) => $q->whereIn('bank_id', $bankIds))
            ->when($currencyIds, fn ($q) => $q->whereIn('currency_id', $currencyIds))
            ->when(! empty($filters['source']), fn ($q) => $q->where('source', $filters['source']))
            ->when(! empty($filters['market']), fn ($q) => $q->where('market', $filters['market']))
            ->orderByDesc('observed_at')
            ->limit(500)
            ->get();

        return RateChangeResource::collection($rows);
    }
}
