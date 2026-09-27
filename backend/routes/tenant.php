<?php

declare(strict_types=1);

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;
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
*/

Route::middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'api',
])->prefix('api')->group(function () {
    Route::get('/health', HealthController::class);
});
