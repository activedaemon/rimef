<?php

use App\Models\User;
use App\Providers\FortifyServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

const MEMBER_PASSWORD = 'mot-de-passe-solide';

beforeEach(function () {
    $this->member = User::factory()->create([
        'email' => 'aminata@example.org',
        'password' => MEMBER_PASSWORD,
    ]);
});

it('logs in an active member', function () {
    $this->postJson($this->tenantUrl('/api/login'), [
        'email' => 'aminata@example.org',
        'password' => MEMBER_PASSWORD,
    ])->assertOk();

    $this->assertAuthenticatedAs($this->member);
});

it('logs in whatever the case of the email', function () {
    $this->postJson($this->tenantUrl('/api/login'), [
        'email' => 'Aminata@Example.org',
        'password' => MEMBER_PASSWORD,
    ])->assertOk();

    $this->assertAuthenticatedAs($this->member);
});

it('returns 422 with a French message for a wrong password', function () {
    $this->postJson($this->tenantUrl('/api/login'), [
        'email' => 'aminata@example.org',
        'password' => 'mauvais-mot-de-passe',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Ces identifiants ne correspondent pas à nos enregistrements.']);

    $this->assertGuest();
});

it('returns 422 for a deactivated account even with the right password', function () {
    $this->member->update(['is_active' => false]);

    $this->postJson($this->tenantUrl('/api/login'), [
        'email' => 'aminata@example.org',
        'password' => MEMBER_PASSWORD,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email' => 'Votre compte est désactivé.']);

    $this->assertGuest();
});

it('returns 429 once the login attempts per minute are exhausted', function () {
    foreach (range(1, FortifyServiceProvider::LOGIN_ATTEMPTS_PER_MINUTE) as $attempt) {
        $this->postJson($this->tenantUrl('/api/login'), [
            'email' => 'aminata@example.org',
            'password' => 'mauvais-mot-de-passe',
        ])->assertUnprocessable();
    }

    $this->postJson($this->tenantUrl('/api/login'), [
        'email' => 'aminata@example.org',
        'password' => MEMBER_PASSWORD,
    ])->assertTooManyRequests();
});

it('returns 409 when the member is already logged in', function () {
    $this->actingAs($this->member)
        ->postJson($this->tenantUrl('/api/login'), [
            'email' => 'aminata@example.org',
            'password' => MEMBER_PASSWORD,
        ])
        ->assertConflict();
});

it('returns 404 on the supervision domain', function () {
    $this->postJson('http://supervisor.rimef.localhost/api/login', [
        'email' => 'aminata@example.org',
        'password' => MEMBER_PASSWORD,
    ])->assertNotFound();

    $this->assertGuest();
});

it('logs out the member', function () {
    $this->actingAs($this->member)
        ->postJson($this->tenantUrl('/api/logout'))
        ->assertNoContent();

    $this->assertGuest('web');
});
