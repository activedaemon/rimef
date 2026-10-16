<?php

use App\Enums\Role;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    Storage::fake('local');

    $this->mediator = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo']);
    $this->mediator->assignRole(Role::Member);
    $this->profile = MemberProfile::factory()->for($this->mediator)->create(['photo_path' => 'members/1.webp']);
    Storage::disk('local')->put('members/1.webp', 'image');
});

it('requires an open session', function () {
    $this->get($this->tenantUrl("/api/members/{$this->mediator->id}/photo"))->assertUnauthorized();
});

it('serves the photo of a mediator to members, with a long private cache', function () {
    $this->actingAs($this->mediator)
        ->get($this->tenantUrl("/api/members/{$this->mediator->id}/photo"))
        ->assertOk()
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, private');
});

it('serves the photo of an active member of the team, without sheet', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);
    MemberProfile::factory()->for($admin)->create(['photo_path' => 'members/1.webp']);

    $this->actingAs($this->mediator)
        ->get($this->tenantUrl("/api/members/{$admin->id}/photo"))
        ->assertOk();
});

it('returns 404 without a photo or for an account neither listed nor in the team', function () {
    $withoutPhoto = User::factory()->create();
    $withoutPhoto->assignRole(Role::Member);
    $inactiveAdmin = User::factory()->inactive()->create();
    $inactiveAdmin->assignRole(Role::Admin);
    MemberProfile::factory()->for($inactiveAdmin)->create(['photo_path' => 'members/1.webp']);
    $noRole = User::factory()->create();
    MemberProfile::factory()->for($noRole)->create(['photo_path' => 'members/1.webp']);

    $this->actingAs($this->mediator);
    $this->get($this->tenantUrl("/api/members/{$withoutPhoto->id}/photo"))->assertNotFound();
    $this->get($this->tenantUrl("/api/members/{$inactiveAdmin->id}/photo"))->assertNotFound();
    $this->get($this->tenantUrl("/api/members/{$noRole->id}/photo"))->assertNotFound();
});

it('gives the photo url in the directory, versioned by the last update', function () {
    $this->actingAs($this->mediator)
        ->getJson($this->tenantUrl('/api/members'))
        ->assertOk()
        ->assertJsonPath(
            'data.0.photo_url',
            "/api/members/{$this->mediator->id}/photo?v={$this->profile->updated_at->getTimestamp()}",
        );
});
