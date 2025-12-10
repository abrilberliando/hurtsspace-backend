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

        // 👇 4. CONTENT SECURITY POLICY (CSP) - DI AKTIFKAN DENGAN WHITELIST
        // Mengizinkan:
        // a. 'self' (domain sendiri)
        // b. 'unsafe-inline' (untuk style/script yang di-inject React/Tailwind)
        // c. Domain Midtrans
        // d. Data URI (untuk avatar fallback)
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' https://cdn.midtrans.com https://app.sandbox.midtrans.com; ";
        $csp .= "style-src 'self' 'unsafe-inline'; "; // Butuh unsafe-inline buat CSS framework
        $csp .= "img-src 'self' * data: https://ui-avatars.com; "; // Memperbolehkan gambar eksternal & data URI
        $csp .= "frame-src 'self' https://app.sandbox.midtrans.com; "; // Wajib buat Midtrans iframe

        $response->headers->set('Content-Security-Policy', $csp);

        // 5. Referrer Policy
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');

        return $response;
    }
}
