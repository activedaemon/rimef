<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes centrales (supervision)
|--------------------------------------------------------------------------
|
| Servies uniquement sur les domaines centraux (voir bootstrap/app.php).
| L'API des tenants est dans routes/tenant.php.
|
*/

Route::get('/health', HealthController::class);
