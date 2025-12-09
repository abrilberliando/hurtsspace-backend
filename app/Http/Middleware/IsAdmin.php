<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Cek apakah user punya role 'admin'
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'message' => 'Eits, Anda bukan Admin! Akses Ditolak.'
            ], 403);
        }

        return $next($request);
    }
}
