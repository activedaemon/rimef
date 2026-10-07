<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            // API centrale (supervision) : limitée aux domaines centraux.
            // L'API des tenants est dans routes/tenant.php (TenancyServiceProvider).
            foreach (config('tenancy.central_domains') as $domain) {
                Route::middleware('api')
                    ->prefix('api')
                    ->domain($domain)
                    ->group(base_path('routes/api.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // SPA sur le même domaine : session + CSRF pour les requêtes de frontend/app
        $middleware->statefulApi();

        // API JSON : pas de redirection vers des pages Laravel
        Authenticate::redirectUsing(fn (Request $request) => $request->expectsJson() ? null : '/connexion');
        RedirectIfAuthenticated::redirectUsing(function (Request $request) {
            abort_if($request->expectsJson(), 409, 'Vous êtes déjà connectée.');

            return '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
