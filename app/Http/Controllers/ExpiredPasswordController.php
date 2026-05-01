<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class ExpiredPasswordController extends Controller
{
    public function show()
    {
        return Inertia::render('Auth/PasswordExpired');
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();
        $user->update([
            'password'            => $request->password,
            'password_changed_at' => now(),
        ]);

        AuditService::log('password.renewed', 'Mot de passe renouvelé (expiration)');

        return redirect()->route('library.index')
            ->with('success', 'Mot de passe mis à jour avec succès.');
    }
}
