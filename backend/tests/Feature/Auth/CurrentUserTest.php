<?php

use App\Enums\Role;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

it('returns 401 without an open session', function () {
    $this->getJson($this->tenantUrl('/api/user'))->assertUnauthorized();
});

it('returns the logged in member with her roles', function () {
    $member = User::factory()->create([
        'first_name' => 'Aminata',
        'last_name' => 'Diallo',
        'email' => 'aminata@example.org',
    ]);
    $member->assignRole(Role::Member->value);

    $this->actingAs($member)
        ->getJson($this->tenantUrl('/api/user'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'id' => $member->id,
            'first_name' => 'Aminata',
            'last_name' => 'Diallo',
            'slug' => 'aminata-diallo',
            'name' => 'Aminata Diallo',
            'email' => 'aminata@example.org',
            'roles' => ['member'],
            'photo_url' => null,
        ]]);
});

it('gives the photo of a member of the directory only', function () {
    $member = User::factory()->create();
    $member->assignRole(Role::Member->value);
    $profile = MemberProfile::factory()->for($member)->create(['photo_path' => 'members/1.webp']);

    $this->actingAs($member)
        ->getJson($this->tenantUrl('/api/user'))
        ->assertJsonPath('data.photo_url', "/api/members/{$member->id}/photo?v={$profile->updated_at->getTimestamp()}");

    // Compte hors annuaire (sans rôle member) : sa photo ne serait pas servie
    $member->removeRole(Role::Member->value);
    $this->getJson($this->tenantUrl('/api/user'))->assertJsonPath('data.photo_url', null);
});

it('returns 403 when the account was deactivated during the session', function () {
    $member = User::factory()->inactive()->create();

    $this->actingAs($member)
        ->getJson($this->tenantUrl('/api/user'))
        ->assertForbidden();
});

it('keeps the session between the SPA requests after login', function () {
    User::factory()->create(['email' => 'aminata@example.org', 'password' => 'mot-de-passe-solide']);
    $spaHeaders = ['Referer' => $this->tenantUrl('/connexion')];

    $this->withHeaders($spaHeaders)
        ->get($this->tenantUrl('/api/sanctum/csrf-cookie'))
        ->assertNoContent()
        ->assertCookie('XSRF-TOKEN');

    $this->withHeaders($spaHeaders)
        ->postJson($this->tenantUrl('/api/login'), [
            'email' => 'aminata@example.org',
            'password' => 'mot-de-passe-solide',
        ])
        ->assertOk();

    $this->withHeaders($spaHeaders)
        ->getJson($this->tenantUrl('/api/user'))
        ->assertOk()
        ->assertJsonPath('data.email', 'aminata@example.org');
});

it('stores the sessions in the tenant database', function () {
    // Vrai driver `database` (les tests utilisent des sessions en mémoire), tenant
    // fermé avant la requête comme en production : la session est alors créée avant
    // l'identification du tenant. La base centrale n'a pas de table `sessions`.
    config(['session.driver' => 'database']);
    app('session')->forgetDrivers();
    User::factory()->create(['email' => 'aminata@example.org', 'password' => 'mot-de-passe-solide']);
    tenancy()->end();
    $spaHeaders = ['Referer' => $this->tenantUrl('/connexion')];

    $this->withHeaders($spaHeaders)
        ->postJson($this->tenantUrl('/api/login'), [
            'email' => 'aminata@example.org',
            'password' => 'mot-de-passe-solide',
        ])
        ->assertOk();

    tenancy()->initialize($this->tenant);
    $this->assertDatabaseCount('sessions', 1);
});
