<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CurrentUserController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Routes tenant
|--------------------------------------------------------------------------
|
| API servie sur le domaine d'un tenant (ex. rimef.localhost) : la base du
| tenant est activée avant le traitement de la requête.
| Chargées par TenancyServiceProvider. L'API centrale est dans routes/api.php.
|
| Authentification (SPA, cookie de session Sanctum) :
| - /api/login, /api/logout, /api/forgot-password, /api/reset-password : Fortify
|   (config/fortify.php, même middleware tenant) ;
| - /api/sanctum/csrf-cookie : ci-dessous, tenant identifié avant la session.
| Tout l'espace des membres est réservé aux comptes connectés : les routes
| métier vont dans le groupe auth:sanctum.
|
*/

Route::middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'api',
])->prefix('api')->group(function () {
    Route::get('/health', HealthController::class);

    Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () {
        Route::get('/user', CurrentUserController::class)->name('user.current');
    });
});

Route::middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'web',
])->prefix('api/sanctum')->group(function () {
    Route::get('/csrf-cookie', [CsrfCookieController::class, 'show'])->name('sanctum.csrf-cookie');
});
