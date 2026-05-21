<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StatisticsRequest;
use App\Http\Resources\RateChangeResource;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\RateChange;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RateChangeController extends Controller
{
    /**
     * History of significant rate changes for the requested window.
     * Reuses StatisticsRequest because it accepts the same filters.
     */
    public function index(StatisticsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->filters();

        $bankIds = ! empty($filters['bank_slugs'])
            ? Bank::whereIn('slug', $filters['bank_slugs'])->pluck('id')->all()
            : [];
        $currencyIds = ! empty($filters['currency_codes'])
            ? Currency::whereIn('code', $filters['currency_codes'])->pluck('id')->all()
            : [];

        $rows = RateChange::query()
            ->with(['bank', 'currency'])
            ->whereBetween('observed_at', [$filters['from'], $filters['to']])
            ->when($bankIds !== [], fn ($q) => $q->whereIn('bank_id', $bankIds))
            ->when($currencyIds !== [], fn ($q) => $q->whereIn('currency_id', $currencyIds))
            ->when(! empty($filters['source']), fn ($q) => $q->where('source', $filters['source']))
            ->when(! empty($filters['market']), fn ($q) => $q->where('market', $filters['market']))
            ->orderByDesc('observed_at')
            ->limit(500)
            ->get();

        return RateChangeResource::collection($rows);
    }
}
