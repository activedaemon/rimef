<?php

namespace App\Providers;

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
    }
}
