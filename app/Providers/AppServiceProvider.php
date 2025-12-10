<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit; // 👈 WAJIB IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter; // 👈 WAJIB IMPORT

class AppServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        // Panggil fungsi konfigurasi Rate Limiter di sini
        $this->configureRateLimiting();
    }

    // 👇 FUNGSI BARU KHUSUS RATE LIMITER
    protected function configureRateLimiting(): void
    {
        // 1. Limiter untuk PUBLIC / AUTH (Login/Register)
        RateLimiter::for('auth_public', function (Request $request) {
            // Batasi 5 request per 1 menit per IP
            return Limit::perMinute(5)->by($request->ip());
        });

        // 2. Limiter untuk PROTECTED / LOGGED-IN USER
        RateLimiter::for('auth_protected', function (Request $request) {
            // Batasi 60 request per 1 menit per User ID atau IP
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
