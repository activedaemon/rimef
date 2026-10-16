<?php

use App\Enums\Role;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Notifications\ResetPasswordNotification;
use App\Services\Invitations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

const INVITED_PASSWORD = 'un-mot-de-passe-solide';

beforeEach(function () {
    Notification::fake();

    $inviter = User::factory()->create(['first_name' => 'Hélène', 'last_name' => 'Marchand']);
    $inviter->assignRole(Role::Admin);

    $this->account = app(Invitations::class)->invite(
        ['first_name' => 'Mireille', 'last_name' => 'Sawadogo', 'email' => 'mireille@example.org'],
        [Role::Member],
        $inviter,
    );

    Notification::assertSentTo($this->account, InvitationNotification::class, function (InvitationNotification $notification) {
        $this->token = $notification->token;

        return true;
    });

    $this->accept = fn (array $overrides = []) => $this->postJson($this->tenantUrl('/api/invitation/accept'), array_replace([
        'token' => $this->token,
        'email' => 'mireille@example.org',
        'password' => INVITED_PASSWORD,
        'password_confirmation' => INVITED_PASSWORD,
    ], $overrides));
});

it('refuses to log in before the activation', function () {
    $this->postJson($this->tenantUrl('/api/login'), ['email' => 'mireille@example.org', 'password' => ''])
        ->assertUnprocessable();

    $this->assertGuest();
});

it('activates the account with the chosen password, then the member logs in', function () {
    ($this->accept)()->assertOk();

    $account = $this->account->refresh();
    expect($account->invitation_pending)->toBeFalse()
        ->and($account->is_active)->toBeTrue()
        ->and($account->last_login_at)->toBeNull()
        ->and($account->activated_at)->not->toBeNull()
        ->and($account->memberSince()?->equalTo($account->activated_at))->toBeTrue();

    $this->postJson($this->tenantUrl('/api/login'), ['email' => 'mireille@example.org', 'password' => INVITED_PASSWORD])
        ->assertOk();

    $this->assertAuthenticatedAs($account);
    expect($account->refresh()->last_login_at)->not->toBeNull();
});

it('refuses a wrong or already used link', function () {
    ($this->accept)(['token' => 'faux-jeton'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Ce lien d’activation n’est plus valable. Demandez une nouvelle invitation à l’équipe du réseau.']);

    ($this->accept)()->assertOk();
    ($this->accept)()->assertUnprocessable();
});

it('refuses an expired link', function () {
    $this->travel(8)->days();

    ($this->accept)()->assertUnprocessable();

    expect($this->account->refresh()->invitation_pending)->toBeTrue();
});

it('requires a strong confirmed password', function () {
    ($this->accept)(['password' => 'court', 'password_confirmation' => 'court'])
        ->assertJsonValidationErrors(['password']);
});

it('sends no reset link to an invited account', function () {
    $this->postJson($this->tenantUrl('/api/forgot-password'), ['email' => 'mireille@example.org'])->assertOk();

    Notification::assertNotSentTo($this->account, ResetPasswordNotification::class);
});
