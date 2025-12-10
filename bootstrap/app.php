<?php

use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\EnsureHttpsAndHsts;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Http\Request; // 👈 WAJIB IMPORT

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // Middleware Group Default
        $middleware->api(array_merge([
            SecurityHeaders::class,
            EnsureHttpsAndHsts::class,
        ]));

        $middleware->alias([
            'is_admin' => IsAdmin::class,
            'throttle' => ThrottleRequests::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 👇 SOLUSI FIX: Tangkap AuthenticationException
        $exceptions->dontReport([
            // ... exceptions yang tidak perlu di-report
        ]);

        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                // Di API, jangan redirect ke login, tapi kirim 401 Unauthorized
                return response()->json([
                    'message' => 'Unauthenticated. Token tidak valid atau hilang.'
                ], 401);
            }
        });
        // ... penanganan exception lainnya

    })->create();
