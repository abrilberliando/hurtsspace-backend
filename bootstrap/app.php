<?php

use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\SecurityHeaders; // 👈 WAJIB IMPORT
use App\Http\Middleware\EnsureHttpsAndHsts; // 👈 WAJIB IMPORT
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // 👇 1. CONFIG API GROUP
        // Inject Security Headers & HSTS otomatis ke semua rute API
        $middleware->api(array_merge([
            SecurityHeaders::class,
            EnsureHttpsAndHsts::class,
        ]));

        // 👇 2. REGISTER ALIAS (Pengganti Kernel.php)
        $middleware->alias([
            'is_admin' => IsAdmin::class,
            'throttle' => ThrottleRequests::class,
        ]);

        // 👇 3. MATIKAN CSRF KHUSUS WEBHOOK (KRUSIAL BUAT MIDTRANS!)
        // Biar Midtrans bisa nge-post data tanpa kena blokir 419 Page Expired
        $middleware->validateCsrfTokens(except: [
            'api/webhooks/midtrans',
            'webhooks/*', // Jaga-jaga kalau path berubah
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // 👇 4. HANDLE AUTH ERROR DI API
        // Kalau token salah/expired, jangan redirect ke /login, tapi return 401 JSON
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated. Token tidak valid atau hilang.'
                ], 401);
            }
        });

    })->create();
