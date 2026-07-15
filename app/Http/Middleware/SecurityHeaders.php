<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Mencegah XSS Attacks di Browser Lama
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // 2. Mencegah Clickjacking (Website Lo dimasukkan ke dalam Iframe)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // 3. Mencegah MIME Type Sniffing (Security against wrong Content-Type)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 👇 4. CONTENT SECURITY POLICY (CSP) - DITAMBAH BITESHIP
        $csp = "default-src 'self'; ";

        // Midtrans & Biteship script/data yang mungkin di load client-side
        $csp .= "script-src 'self' 'unsafe-inline' https://cdn.midtrans.com https://app.sandbox.midtrans.com https://api.biteship.com; ";

        // 👇 WAJIB FIX: connect-src untuk API eksternal (Biteship)
        $csp .= "connect-src 'self' https://api.biteship.com; "; // 👈 FIX DITAMBAH DI SINI

        $csp .= "style-src 'self' 'unsafe-inline'; ";
        $csp .= "img-src 'self' * data:; ";
        $csp .= "frame-src 'self' https://app.sandbox.midtrans.com; "; // Midtrans iframe

        $response->headers->set('Content-Security-Policy', $csp);

        // 5. Referrer Policy
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');

        return $response;
    }
}
