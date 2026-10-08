<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        // Règle commune des mots de passe (réinitialisation, puis invitation et profil)
        Password::defaults(fn (): Password => Password::min(12)->max(255));

        // Le superadmin passe toutes les autorisations
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);
    }
}
