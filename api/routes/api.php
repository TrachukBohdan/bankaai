<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SubscriptionController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\RateChangeController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'status'  => 'ok',
        'service' => 'bankaai-api',
        'time'    => now()->toIso8601String(),
    ]);
});

Route::get('/status', [StatusController::class, 'show']);

// Public read endpoints (task §3)
Route::get('/currencies', [CurrencyController::class, 'index']);
Route::get('/banks', [BankController::class, 'index']);
Route::get('/banks/{slug}', [BankController::class, 'show']);
Route::get('/branches/nearest', [BranchController::class, 'nearest']);
Route::get('/rates', [RateController::class, 'index']);
Route::get('/rates/nbu', [RateController::class, 'nbu']);
Route::get('/rates/history', [RateController::class, 'history']);
Route::get('/rates/changes', [RateChangeController::class, 'index']);
Route::get('/rates/statistics', [RateController::class, 'statistics']);

// SPA cookie auth (Sanctum stateful)
Route::post('/auth/register', RegisterController::class);
Route::post('/auth/login', [LoginController::class, 'store']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [LoginController::class, 'destroy']);
    Route::get('/me', [ProfileController::class, 'show']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::get('/me/subscriptions', [SubscriptionController::class, 'index']);
    Route::post('/me/subscriptions', [SubscriptionController::class, 'store']);
    Route::delete('/me/subscriptions/{subscription}', [SubscriptionController::class, 'destroy']);
});
