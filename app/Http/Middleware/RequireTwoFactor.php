<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // 2FA activé mais pas encore vérifié cette session
        if ($user->hasTwoFactorEnabled() && !session('two_factor_verified')) {
            if (!$request->routeIs('two-factor.*') && !$request->routeIs('logout')) {
                return redirect()->route('two-factor.challenge');
            }
        }

        return $next($request);
    }
}
