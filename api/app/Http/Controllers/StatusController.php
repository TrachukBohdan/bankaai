<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Throwable;

class StatusController extends Controller
{
    /**
     * GET /api/status
     *
     * Returns a snapshot of the API's runtime state, including a live MySQL
     * connectivity probe. Used as an end-to-end smoke test from the SPA.
     */
    public function show(): JsonResponse
    {
        $database = $this->probeDatabase();

        return response()->json([
            'service' => 'bankaai-api',
            'status' => $database['connected'] ? 'ok' : 'degraded',
            'app' => [
                'name' => config('app.name'),
                'env' => App::environment(),
                'url' => config('app.url'),
            ],
            'versions' => [
                'laravel' => App::version(),
                'php' => PHP_VERSION,
            ],
            'host' => gethostname() ?: null,
            'server_time' => now()->toIso8601String(),
            'database' => $database,
        ]);
    }

    /**
     * @return array{connection: string, connected: bool, error: ?string}
     */
    private function probeDatabase(): array
    {
        $connection = (string) config('database.default');

        try {
            DB::connection($connection)->getPdo();

            return [
                'connection' => $connection,
                'connected' => true,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'connection' => $connection,
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
