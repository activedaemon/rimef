<?php

declare(strict_types=1);

use App\Http\Controllers\Dev\EmailPreviewController;
use App\Http\Middleware\EnsureDevToolsEnabled;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes de développement
|--------------------------------------------------------------------------
|
| UNIQUEMENT pour le développement : chargées par bootstrap/app.php hors
| production, puis ouvertes en local ou si RIMEF_DEV_TOOLS est activé
| (EnsureDevToolsEnabled ; 404 sinon). Préfixe /api/dev pour être routées vers le backend par Traefik
| (qui n'achemine que /api/* vers Laravel ; le reste va à la SPA).
| Sans middleware 'web' : pas de session (la table `sessions` est par tenant)
| ni de CSRF. Sans contrainte de domaine : accessibles sur rimef.localhost.
|
*/

Route::prefix('api/dev')->name('dev.')->middleware(EnsureDevToolsEnabled::class)->group(function (): void {
    Route::prefix('emails')->name('emails.')->group(function (): void {
        Route::get('/', [EmailPreviewController::class, 'index'])->name('index');
        Route::get('/preview/{template}', [EmailPreviewController::class, 'show'])->name('show');
        // Envoi limité : chaque appel part réellement (vers Mailpit hors local).
        Route::post('/send/{template}', [EmailPreviewController::class, 'send'])->middleware('throttle:10,1')->name('send');
    });
});
