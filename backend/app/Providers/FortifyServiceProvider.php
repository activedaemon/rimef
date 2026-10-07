<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\PasswordResetLinkRequestResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

/**
 * Authentification des membres (SPA, cookie de session Sanctum).
 *
 * Les routes Fortify (/api/login, /api/logout, /api/forgot-password,
 * /api/reset-password) sont servies sur les domaines des tenants uniquement
 * (voir config/fortify.php).
 */
class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Tentatives de connexion autorisées par minute, pour un même email et une même IP.
     */
    public const LOGIN_ATTEMPTS_PER_MINUTE = 5;

    public function register(): void
    {
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, PasswordResetLinkRequestResponse::class);
    }

    public function boot(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::query()
                ->where('email', Str::lower((string) $request->input(Fortify::username())))
                ->first();

            if ($user === null || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('Votre compte est désactivé. Contactez une administratrice du réseau.'),
                ]);
            }

            return $user;
        });

        RateLimiter::for('login', function (Request $request): Limit {
            $throttleKey = Str::transliterate(Str::lower((string) $request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(self::LOGIN_ATTEMPTS_PER_MINUTE)->by($throttleKey);
        });
    }
}
