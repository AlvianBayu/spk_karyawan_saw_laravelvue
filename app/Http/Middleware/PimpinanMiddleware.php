<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PimpinanMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role === 'pimpinan') {
            return $next($request);
        }

        // 👇 UBAH BARIS INI (Gunakan path '/login' langsung) 👇
        return redirect('/login')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
    }
}