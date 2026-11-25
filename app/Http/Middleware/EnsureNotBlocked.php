<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isBlocked()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda telah diblokir oleh Administrator.',
                ], 403);
            }

            return redirect('/?auth=login')->withErrors([
                'email' => 'Akun Anda telah diblokir oleh Administrator.',
            ]);
        }

        return $next($request);
    }
}
