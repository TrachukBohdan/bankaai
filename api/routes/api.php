<?php

use App\Http\Controllers\StatusController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'bankaai-api',
        'time' => now()->toIso8601String(),
    ]);
});

Route::get('/status', [StatusController::class, 'show']);
