<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPasswordExpiry
{
    private const EXEMPT_ROUTES = ['password.update', 'logout', 'two-factor.*'];

    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        foreach (self::EXEMPT_ROUTES as $route) {
            if ($request->routeIs($route)) {
                return $next($request);
            }
        }

        if ($user->isPasswordExpired() && !$request->routeIs('password.expired')) {
            return redirect()->route('password.expired');
        }

        return $next($request);
    }
}
