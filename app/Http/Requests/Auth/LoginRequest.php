<?php

namespace App\Http\Requests\Auth;

use App\Mail\SuspiciousActivityAlert;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (!Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // Alerte après 3 tentatives échouées sur le même compte
            $attempts = RateLimiter::attempts($this->throttleKey());
            if ($attempts >= 3) {
                $this->notifyAdmins(
                    'Tentatives de connexion échouées',
                    "{$attempts} tentatives échouées pour le compte : {$this->email}"
                );
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (!Auth::user()->is_active) {
            Auth::logout();
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Votre compte a été désactivé. Contactez un administrateur.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $this->notifyAdmins(
            'Compte bloqué — trop de tentatives',
            "Le compte {$this->email} a été bloqué après 5 tentatives depuis l'IP {$this->ip()}"
        );

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }

    private function notifyAdmins(string $type, string $detail): void
    {
        try {
            $admins = User::where('role', 'admin')->where('is_active', true)->get();
            $alert  = new SuspiciousActivityAlert(
                type:       $type,
                detail:     $detail,
                ipAddress:  $this->ip(),
                occurredAt: now()->format('d/m/Y H:i:s'),
            );

            foreach ($admins as $admin) {
                Mail::to($admin->email)->send($alert);
            }
        } catch (\Throwable) {
            // Ne jamais bloquer la requête si l'envoi du mail échoue
        }
    }
}
