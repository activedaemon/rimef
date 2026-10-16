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

        // Le superadmin passe toutes les autorisations, sauf celles qui visent un compte :
        // UserPolicy les décide pour lui aussi (son propre compte est protégé)
        Gate::before(fn (User $user, string $ability, array $arguments): ?bool => $user->isSuperAdmin() && ! (($arguments[0] ?? null) instanceof User) ? true : null);

        // Rubrique Administration : administratrices (et superadmin, par la règle ci-dessus)
        Gate::define('administer', fn (User $user): bool => $user->isAdmin());
    }
}
