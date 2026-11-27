<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak. Hanya Admin yang diizinkan.'], 403);
            }

            abort(403, 'Akses ditolak. Hanya Admin yang diizinkan.');
        }

        if (auth()->user()->isBlocked()) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda telah diblokir.']);
        }

        return $next($request);
    }
}
