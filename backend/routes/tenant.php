<?php

declare(strict_types=1);

use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\CurrentUserController;
use App\Http\Controllers\Api\EventParticipationController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NotificationController;
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
        Route::get('/home', HomeController::class)->name('home');

        Route::put('/events/{event}/participation', [EventParticipationController::class, 'store'])
            ->whereNumber('event')
            ->name('events.participation.store');
        Route::delete('/events/{event}/participation', [EventParticipationController::class, 'destroy'])
            ->whereNumber('event')
            ->name('events.participation.destroy');

        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::get('/members/filters', [MemberController::class, 'filters'])->name('members.filters');
        Route::get('/members/{user:slug}', [MemberController::class, 'show'])
            ->where('user', '[a-z0-9-]+')
            ->name('members.show');
        Route::post('/members/{user:slug}/contact', [MemberController::class, 'contact'])
            ->where('user', '[a-z0-9-]+')
            ->middleware('throttle:10,60')
            ->name('members.contact');
        Route::get('/members/{user}/photo', [MemberController::class, 'photo'])
            ->whereNumber('user')
            ->name('members.photo');
        Route::put('/members/{user}/favorite', [MemberController::class, 'favorite'])
            ->whereNumber('user')
            ->name('members.favorite');
        Route::delete('/members/{user}/favorite', [MemberController::class, 'unfavorite'])
            ->whereNumber('user')
            ->name('members.unfavorite');

        // Messagerie interne
        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])
            ->whereNumber('conversation')
            ->name('conversations.show');
        Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])
            ->whereNumber('conversation')
            ->name('conversations.messages');
        Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'store'])
            ->whereNumber('conversation')
            ->middleware('throttle:30,1')
            ->name('conversations.messages.store');
        Route::put('/conversations/{conversation}/read', [ConversationController::class, 'read'])
            ->whereNumber('conversation')
            ->name('conversations.read');

        // Cloche
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
            ->name('notifications.unread-count');
        Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
            ->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])
            ->whereUuid('notification')
            ->name('notifications.read');
    });
});

Route::middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'web',
])->prefix('api/sanctum')->group(function () {
    Route::get('/csrf-cookie', [CsrfCookieController::class, 'show'])->name('sanctum.csrf-cookie');
});
