<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    $this->event = Event::factory()->create(['starts_at' => now()->addMonth()]);
});

dataset('non-members', [
    'administratrice sans fiche' => [Role::Admin],
    'superadmin' => [Role::SuperAdmin],
]);

it('refuses participation and the meeting proposal to an account without the member role', function (Role $role) {
    $account = User::factory()->create();
    $account->assignRole($role);

    $this->actingAs($account)->putJson($this->tenantUrl("/api/events/{$this->event->id}/participation"))
        ->assertForbidden()
        ->assertJsonPath('message', 'Il faut être membre du réseau pour participer à un événement.');
    $this->actingAs($account)->putJson($this->tenantUrl("/api/events/{$this->event->id}/meeting"))
        ->assertForbidden();

    expect($this->event->participants()->count())->toBe(0);
})->with('non-members');

it('lets an administrator who is also a member take part', function () {
    $account = User::factory()->create();
    $account->assignRole([Role::Admin, Role::Member]);

    $this->actingAs($account)->putJson($this->tenantUrl("/api/events/{$this->event->id}/participation"))
        ->assertNoContent();
});

it('still lets an account that lost the member role withdraw', function () {
    $account = User::factory()->create();
    $account->assignRole(Role::Admin);
    $this->event->participants()->attach($account->id);

    $this->actingAs($account)->deleteJson($this->tenantUrl("/api/events/{$this->event->id}/participation"))
        ->assertNoContent();

    expect($this->event->participants()->count())->toBe(0);
});
