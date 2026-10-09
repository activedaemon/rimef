<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

function member(string $lastName): User
{
    $user = User::factory()->create(['first_name' => 'Médiatrice', 'last_name' => $lastName]);
    $user->assignRole(Role::Member);

    return $user;
}

beforeEach(function () {
    $this->viewer = member('Lectrice');
    $this->actingAs($this->viewer);
});

it('adds and removes a favorite, idempotently, for the connected account only', function () {
    $aminata = member('Diallo');
    $url = $this->tenantUrl("/api/members/{$aminata->id}/favorite");

    $this->putJson($url)->assertNoContent();
    $this->putJson($url)->assertNoContent();
    expect($this->viewer->favorites()->pluck('users.id')->all())->toBe([$aminata->id]);

    $this->deleteJson($url)->assertNoContent();
    expect($this->viewer->favorites()->count())->toBe(0);
});

it('refuses to favorite an account outside the directory', function () {
    $adminOnly = User::factory()->create();
    $adminOnly->assignRole(Role::Admin);

    $this->putJson($this->tenantUrl("/api/members/{$adminOnly->id}/favorite"))->assertNotFound();
});

it('marks and filters the favorites of the connected account', function () {
    $aminata = member('Diallo');
    member('Dubois');
    $this->viewer->favorites()->attach($aminata);
    // Le favori d'une autre personne ne compte pas
    member('Autre')->favorites()->attach($this->viewer);

    $this->getJson($this->tenantUrl('/api/members'))
        ->assertJsonPath('data.*.is_favorite', [false, true, false, false]);

    $this->getJson($this->tenantUrl('/api/members?favorites=1'))
        ->assertJsonPath('data.*.name', ['Médiatrice Diallo']);
});

it('gives the next upcoming event of each mediator, past events ignored', function () {
    $aminata = member('Diallo');
    Event::factory()->create(['title' => 'Passé', 'starts_at' => now()->subDay()])->participants()->attach($aminata);
    Event::factory()->create(['title' => 'Sommet', 'starts_at' => now()->addMonth()])->participants()->attach($aminata);
    $forum = Event::factory()->create(['title' => 'Forum', 'starts_at' => now()->addWeek()]);
    $forum->participants()->attach($aminata);

    $this->getJson($this->tenantUrl('/api/members?q=Diallo'))
        ->assertJsonPath('data.0.next_event.id', $forum->id)
        ->assertJsonPath('data.0.next_event.title', 'Forum');

    $this->getJson($this->tenantUrl('/api/members?q=Lectrice'))
        ->assertJsonPath('data.0.next_event', null);
});

it('filters mediators attending an upcoming event and sorts by the nearest event', function () {
    $later = member('Plus-tard');
    $soon = member('Bientot');
    member('Aucun');
    Event::factory()->create(['starts_at' => now()->addMonth()])->participants()->attach($later);
    Event::factory()->create(['starts_at' => now()->addWeek()])->participants()->attach($soon);

    $this->getJson($this->tenantUrl('/api/members?upcoming=1'))
        ->assertJsonPath('data.*.name', ['Médiatrice Bientot', 'Médiatrice Plus-tard']);

    $this->getJson($this->tenantUrl('/api/members?sort=event'))
        ->assertJsonPath('data.*.name', [
            'Médiatrice Bientot',
            'Médiatrice Plus-tard',
            'Médiatrice Aucun',
            'Médiatrice Lectrice',
        ]);
});
