<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Memastikan hanya pengguna dengan role 'admin' yang bisa mengakses rute /admin/*.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses ditolak. Hanya Admin yang diizinkan.'], 403);
            }

            abort(403, 'Akses ditolak. Hanya Admin yang diizinkan.');
        }

        // Blokir user tidak bisa login (cek is_blocked)
        if (auth()->user()->is_blocked) {
            auth()->logout();
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda telah diblokir.']);
        }

        return $next($request);
    }
}
