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

it('returns 404 without a photo or for an account outside the directory', function () {
    $withoutPhoto = User::factory()->create();
    $withoutPhoto->assignRole(Role::Member);
    $adminOnly = User::factory()->create();
    $adminOnly->assignRole(Role::Admin);
    MemberProfile::factory()->for($adminOnly)->create(['photo_path' => 'members/1.webp']);

    $this->actingAs($this->mediator);
    $this->get($this->tenantUrl("/api/members/{$withoutPhoto->id}/photo"))->assertNotFound();
    $this->get($this->tenantUrl("/api/members/{$adminOnly->id}/photo"))->assertNotFound();
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
