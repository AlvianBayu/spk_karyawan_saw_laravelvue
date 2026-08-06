<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Jika yang login BUKAN admin, tolak aksesnya (Error 403)
        if (auth()->check() && auth()->user()->role !== 'admin') {
            abort(403, 'Akses Ditolak. Anda hanya memiliki akses baca (Read-Only).');
        }

        return $next($request);
    }
}