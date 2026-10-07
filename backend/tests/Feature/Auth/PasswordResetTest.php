<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

const RESET_LINK_SENT_MESSAGE = 'Si un compte correspond à cette adresse, un lien de réinitialisation vient de vous être envoyé par email.';

it('emails a reset link pointing to the SPA of the tenant', function () {
    Notification::fake();
    $member = User::factory()->create(['email' => 'aminata@example.org']);

    $this->postJson($this->tenantUrl('/api/forgot-password'), ['email' => 'aminata@example.org'])
        ->assertOk()
        ->assertJson(['message' => RESET_LINK_SENT_MESSAGE]);

    Notification::assertSentTo($member, ResetPasswordNotification::class, function (ResetPasswordNotification $notification) use ($member) {
        $url = $notification->toMail($member)->actionUrl;

        return str_starts_with($url, $this->tenantUrl('/reinitialiser-mot-de-passe?token='))
            && str_contains($url, 'email=aminata%40example.org');
    });
});

it('answers the same way for an unknown email without sending anything', function () {
    Notification::fake();

    $this->postJson($this->tenantUrl('/api/forgot-password'), ['email' => 'inconnue@example.org'])
        ->assertOk()
        ->assertJson(['message' => RESET_LINK_SENT_MESSAGE]);

    Notification::assertNothingSent();
});

it('resets the password with a valid token', function () {
    $member = User::factory()->create(['email' => 'aminata@example.org']);
    $token = Password::broker()->createToken($member);

    $this->postJson($this->tenantUrl('/api/reset-password'), [
        'token' => $token,
        'email' => 'aminata@example.org',
        'password' => 'nouveau-mot-de-passe',
        'password_confirmation' => 'nouveau-mot-de-passe',
    ])->assertOk();

    expect(Hash::check('nouveau-mot-de-passe', $member->fresh()->password))->toBeTrue();
});

it('returns 422 for a password shorter than 12 characters', function () {
    $member = User::factory()->create(['email' => 'aminata@example.org']);
    $token = Password::broker()->createToken($member);

    $this->postJson($this->tenantUrl('/api/reset-password'), [
        'token' => $token,
        'email' => 'aminata@example.org',
        'password' => 'trop-court',
        'password_confirmation' => 'trop-court',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password' => 'Le champ mot de passe doit contenir au moins 12 caractères.']);
});

it('returns 422 for an invalid token', function () {
    User::factory()->create(['email' => 'aminata@example.org']);

    $this->postJson($this->tenantUrl('/api/reset-password'), [
        'token' => 'jeton-invalide',
        'email' => 'aminata@example.org',
        'password' => 'nouveau-mot-de-passe',
        'password_confirmation' => 'nouveau-mot-de-passe',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
