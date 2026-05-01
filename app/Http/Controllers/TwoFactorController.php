<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class TwoFactorController extends Controller
{
    // ── Affiche la page de défi 2FA ──────────────────────────────────────────
    public function challenge()
    {
        if (!Auth::user()->hasTwoFactorEnabled()) {
            return redirect()->route('library.index');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    // ── Vérifie le code 2FA ──────────────────────────────────────────────────
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6',
        ]);

        $user      = Auth::user();
        $google2fa = app('pragmarx.google2fa');
        $valid     = $google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->code
        );

        if (!$valid) {
            AuditService::log('2fa.failed', 'Échec de vérification du code 2FA');
            return back()->withErrors(['code' => 'Code incorrect. Vérifiez votre application d\'authentification.']);
        }

        session(['two_factor_verified' => true]);
        AuditService::log('2fa.verified', 'Vérification 2FA réussie');

        return redirect()->intended(route('library.index'));
    }

    // ── Affiche la page de configuration 2FA ─────────────────────────────────
    public function setup()
    {
        $user      = Auth::user();
        $google2fa = app('pragmarx.google2fa');

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit');
        }

        // Génère un secret temporaire stocké en session
        if (!session('2fa_setup_secret')) {
            session(['2fa_setup_secret' => $google2fa->generateSecretKey()]);
        }

        $secret = session('2fa_setup_secret');
        $qrUrl  = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return Inertia::render('Auth/TwoFactorSetup', [
            'qrUrl'  => $qrUrl,
            'secret' => $secret,
        ]);
    }

    // ── Active le 2FA après confirmation du code ──────────────────────────────
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6',
        ]);

        $user      = Auth::user();
        $google2fa = app('pragmarx.google2fa');
        $secret    = session('2fa_setup_secret');

        if (!$secret || !$google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Code incorrect. Réessayez avec votre application.']);
        }

        $user->update([
            'two_factor_secret'       => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ]);

        session()->forget('2fa_setup_secret');
        session(['two_factor_verified' => true]);

        AuditService::log('2fa.enabled', 'Double authentification activée');

        return redirect()->route('profile.edit')
            ->with('success', 'Double authentification activée avec succès.');
    }

    // ── Désactive le 2FA ──────────────────────────────────────────────────────
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = Auth::user();
        $user->update([
            'two_factor_secret'       => null,
            'two_factor_confirmed_at' => null,
        ]);

        session()->forget('two_factor_verified');

        AuditService::log('2fa.disabled', 'Double authentification désactivée');

        return redirect()->route('profile.edit')
            ->with('success', 'Double authentification désactivée.');
    }
}
