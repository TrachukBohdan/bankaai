<?php

declare(strict_types=1);

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA mode: stateful API requests reuse the session cookie
        // issued by /sanctum/csrf-cookie. Without this middleware the cookie
        // is ignored and every /api/* request would be unauthenticated.
        $middleware->statefulApi();

        // CSRF protection still applies to web routes; nothing to do here for
        // /api/* because Sanctum's middleware handles CSRF verification when
        // the request is detected as "stateful".
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API consumers should never be 302-redirected to a non-existent
        // "login" route on auth failure; they expect a clean 401 JSON.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return null;
        });
    })
    ->create();
