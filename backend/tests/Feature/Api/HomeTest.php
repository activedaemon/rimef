<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

function homeMember(string $lastName, ?string $photo = null): User
{
    $user = User::factory()->create(['first_name' => 'Médiatrice', 'last_name' => $lastName]);
    $user->assignRole(Role::Member);
    if ($photo !== null) {
        MemberProfile::factory()->for($user)->create(['photo_path' => $photo]);
    }

    return $user;
}

beforeEach(function () {
    $this->viewer = homeMember('Lectrice');
    $this->actingAs($this->viewer);
});

it('requires an open session', function () {
    auth()->logout();
    $this->app['auth']->forgetGuards();

    $this->getJson($this->tenantUrl('/api/home'))->assertUnauthorized();
});

it('gives the featured event with its attendees, photos first, and the participation of the viewer', function () {
    $withPhoto = homeMember('Zeta', 'members/1.webp');
    $withoutPhoto = homeMember('Alpha');
    $adminOnly = User::factory()->create();
    $adminOnly->assignRole(Role::Admin);

    $forum = Event::factory()->create([
        'title' => 'Paris Peace Forum',
        'starts_at' => now()->addMonth(),
        'is_featured' => true,
        'description' => 'Un espace de dialogue.',
    ]);
    $forum->participants()->attach([$withoutPhoto->id, $withPhoto->id, $adminOnly->id, $this->viewer->id]);

    $this->getJson($this->tenantUrl('/api/home'))
        ->assertOk()
        ->assertJsonPath('data.featured_event.title', 'Paris Peace Forum')
        ->assertJsonPath('data.featured_event.description', 'Un espace de dialogue.')
        ->assertJsonPath('data.featured_event.attendee_count', 3)
        ->assertJsonPath('data.featured_event.is_participating', true)
        ->assertJsonPath('data.featured_event.attendees.0.name', 'Médiatrice Zeta')
        ->assertJsonPath('data.featured_event.attendees.1.name', 'Médiatrice Alpha')
        ->assertJsonCount(3, 'data.featured_event.attendees');
});

it('lists the next meetings, the featured event and past events excluded', function () {
    Event::factory()->create(['title' => 'Passé', 'starts_at' => now()->subDay()]);
    Event::factory()->create(['title' => 'À la une', 'starts_at' => now()->addDay(), 'is_featured' => true]);
    Event::factory()->create(['title' => 'Plus tard', 'starts_at' => now()->addWeeks(2)]);
    Event::factory()->create(['title' => 'Bientôt', 'starts_at' => now()->addWeek()]);

    $this->getJson($this->tenantUrl('/api/home'))
        ->assertJsonPath('data.meetings.*.title', ['Bientôt', 'Plus tard']);
});

it('has no featured event when none is planned', function () {
    $this->getJson($this->tenantUrl('/api/home'))
        ->assertJsonPath('data.featured_event', null)
        ->assertJsonPath('data.meetings', []);
});

it('gives the three latest mediators who joined, inactive accounts excluded', function () {
    $this->travel(1)->minutes();
    homeMember('Premiere');
    $this->travel(1)->minutes();
    homeMember('Deuxieme');
    $this->travel(1)->minutes();
    homeMember('Troisieme')->update(['is_active' => false]);
    $this->travel(1)->minutes();
    homeMember('Quatrieme');

    $this->getJson($this->tenantUrl('/api/home'))
        ->assertJsonPath('data.news.*.name', ['Médiatrice Quatrieme', 'Médiatrice Deuxieme', 'Médiatrice Premiere']);
});

it('registers and cancels the participation of the viewer', function () {
    $event = Event::factory()->create();
    $url = $this->tenantUrl("/api/events/{$event->id}/participation");

    $this->putJson($url)->assertNoContent();
    $this->putJson($url)->assertNoContent();
    expect($event->participants()->pluck('users.id')->all())->toBe([$this->viewer->id]);

    $this->deleteJson($url)->assertNoContent();
    expect($event->participants()->count())->toBe(0);
});
