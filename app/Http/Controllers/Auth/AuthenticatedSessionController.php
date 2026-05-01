<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'last_login_at' => now(),
            'login_count'   => ($user->login_count ?? 0) + 1,
        ]);

        AuditService::log('auth.login', "Connexion réussie ({$user->email})");

        return redirect()->intended(route('library.index', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if ($user) {
            AuditService::log('auth.logout', "Déconnexion ({$user->email})");
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
