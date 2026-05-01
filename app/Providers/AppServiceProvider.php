<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);

        // Politique de mot de passe globale (tous les formulaires qui utilisent Password::defaults())
        Password::defaults(fn () =>
            Password::min(12)
                ->mixedCase()   // au moins 1 majuscule + 1 minuscule
                ->numbers()     // au moins 1 chiffre
                ->symbols()     // au moins 1 caractère spécial
                ->uncompromised() // vérifie contre HaveIBeenPwned
        );

        Gate::define('admin', fn ($user) => $user->isAdmin());
        Gate::define('upload', fn ($user) => $user->canUpload());
        Gate::define('delete-document', fn ($user) => $user->canDelete());
        Gate::define('download', fn ($user) => $user->canDownload());
    }
}
