<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Admin\AccountController;
use App\Http\Controllers\Api\Admin\InvitationController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\CurrentUserController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventMeetingController;
use App\Http\Controllers\Api\EventParticipationController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\InvitationAcceptController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\TeamController;
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

    // Activation d'un compte invité, depuis le lien reçu par email (sans session)
    Route::post('/invitation/accept', InvitationAcceptController::class)
        ->middleware('throttle:6,1')
        ->name('invitation.accept');

    Route::middleware(['auth:sanctum', EnsureUserIsActive::class])->group(function () {
        Route::get('/user', CurrentUserController::class)->name('user.current');
        Route::get('/home', HomeController::class)->name('home');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])
            ->middleware('throttle:10,10')
            ->name('profile.photo.update');
        Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/filters', [EventController::class, 'filters'])->name('events.filters');
        Route::get('/events/calendar', [EventController::class, 'calendar'])->name('events.calendar');
        Route::get('/events/{event:slug}', [EventController::class, 'show'])
            ->where('event', '[a-z0-9-]+')
            ->name('events.show');
        Route::get('/events/{event}/photo', [EventController::class, 'photo'])
            ->whereNumber('event')
            ->name('events.photo');
        Route::put('/events/{event}/participation', [EventParticipationController::class, 'store'])
            ->whereNumber('event')
            ->name('events.participation.store');
        Route::delete('/events/{event}/participation', [EventParticipationController::class, 'destroy'])
            ->whereNumber('event')
            ->name('events.participation.destroy');
        Route::put('/events/{event}/meeting', [EventMeetingController::class, 'store'])
            ->whereNumber('event')
            ->name('events.meeting.store');
        Route::delete('/events/{event}/meeting', [EventMeetingController::class, 'destroy'])
            ->whereNumber('event')
            ->name('events.meeting.destroy');

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

        // Bibliothèque de ressources
        Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');
        Route::get('/resources/filters', [ResourceController::class, 'filters'])->name('resources.filters');
        Route::get('/resources/voices', [ResourceController::class, 'voices'])->name('resources.voices');
        Route::get('/resources/{resource:slug}', [ResourceController::class, 'show'])
            ->where('resource', '[a-z0-9-]+')
            ->name('resources.show');
        Route::put('/resources/{resource}/favorite', [ResourceController::class, 'favorite'])
            ->whereNumber('resource')
            ->name('resources.favorite');
        Route::delete('/resources/{resource}/favorite', [ResourceController::class, 'unfavorite'])
            ->whereNumber('resource')
            ->name('resources.unfavorite');

        // Messagerie interne
        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::post('/conversations', [ConversationController::class, 'start'])
            ->middleware('throttle:10,60')
            ->name('conversations.start');
        Route::get('/team', TeamController::class)->name('team');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])
            ->whereNumber('conversation')
            ->name('conversations.show');
        Route::delete('/conversations/{conversation}', [ConversationController::class, 'clear'])
            ->whereNumber('conversation')
            ->name('conversations.clear');
        Route::get('/conversations/{conversation}/messages', [ConversationController::class, 'messages'])
            ->whereNumber('conversation')
            ->name('conversations.messages');
        Route::post('/conversations/{conversation}/messages', [ConversationController::class, 'store'])
            ->whereNumber('conversation')
            ->middleware('throttle:30,1')
            ->name('conversations.messages.store');
        Route::patch('/conversations/{conversation}/messages/{message}', [ConversationController::class, 'update'])
            ->whereNumber(['conversation', 'message'])
            ->middleware('throttle:30,1')
            ->name('conversations.messages.update');
        Route::delete('/conversations/{conversation}/messages/{message}', [ConversationController::class, 'destroy'])
            ->whereNumber(['conversation', 'message'])
            ->middleware('throttle:30,1')
            ->name('conversations.messages.destroy');
        Route::put('/conversations/{conversation}/read', [ConversationController::class, 'read'])
            ->whereNumber('conversation')
            ->name('conversations.read');

        // Rubrique Administration (administratrices et superadmin) ; droits fins : UserPolicy
        Route::middleware('can:administer')->prefix('admin')->name('admin.')->group(function () {
            Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('/accounts/{user}', [AccountController::class, 'show'])
                ->whereNumber('user')
                ->name('accounts.show');
            Route::put('/accounts/{user}/activation', [AccountController::class, 'activate'])
                ->whereNumber('user')
                ->name('accounts.activate');
            Route::delete('/accounts/{user}/activation', [AccountController::class, 'deactivate'])
                ->whereNumber('user')
                ->name('accounts.deactivate');
            Route::put('/accounts/{user}/roles', [AccountController::class, 'updateRoles'])
                ->whereNumber('user')
                ->name('accounts.roles');
            Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])
                ->whereNumber('user')
                ->name('accounts.destroy');

            Route::post('/invitations', [InvitationController::class, 'store'])
                ->middleware('throttle:30,60')
                ->name('invitations.store');
            Route::post('/accounts/{user}/invitation', [InvitationController::class, 'resend'])
                ->whereNumber('user')
                ->middleware('throttle:30,60')
                ->name('invitations.resend');
            Route::delete('/accounts/{user}/invitation', [InvitationController::class, 'destroy'])
                ->whereNumber('user')
                ->name('invitations.destroy');
        });

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
