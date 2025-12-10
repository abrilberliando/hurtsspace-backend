<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHttpsAndHsts
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Force HTTPS (Jika belum HTTPS, redirect ke HTTPS)
        if (!$request->secure() && config('app.env') === 'production') {
            return redirect()->secure($request->getRequestUri());
        }

        $response = $next($request);

        // 2. Tambahkan Header HSTS (Wajib Setelah Response Dibuat)
        // Set max-age 1 tahun (31536000 detik)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
