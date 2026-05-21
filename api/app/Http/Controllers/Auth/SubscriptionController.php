<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SubscriptionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $subs = $request->user()
            ->subscriptions()
            ->with(['bank', 'currency'])
            ->latest()
            ->get();

        return SubscriptionResource::collection($subs);
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $bankId = $request->filled('bank_slug')
            ? Bank::where('slug', $request->input('bank_slug'))->value('id')
            : null;
        $currencyId = $request->filled('currency_code')
            ? Currency::where('code', strtoupper((string) $request->input('currency_code')))->value('id')
            : null;

        $sub = Subscription::create([
            'user_id'       => $request->user()->id,
            'bank_id'       => $bankId,
            'currency_id'   => $currencyId,
            'threshold_pct' => $request->input('threshold_pct', 5.0),
        ]);

        $sub->load(['bank', 'currency']);

        return SubscriptionResource::make($sub)
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Subscription $subscription): JsonResponse
    {
        if ($subscription->user_id !== $request->user()->id) {
            abort(403);
        }
        $subscription->delete();

        return response()->json(['message' => 'Subscription removed']);
    }
}
