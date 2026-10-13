<?php

use App\Enums\EventType;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

function agendaMember(string $firstName, string $lastName = 'Médiatrice'): User
{
    $user = User::factory()->create(['first_name' => $firstName, 'last_name' => $lastName]);
    $user->assignRole(Role::Member);

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 * @param  list<string>  $themes
 */
function agendaEvent(string $title, string $startsAt, array $attributes = [], array $themes = []): Event
{
    $event = Event::factory()->create(['title' => $title, 'starts_at' => Carbon::parse($startsAt), ...$attributes]);
    $event->themes()->attach(collect($themes)->map(fn (string $name): int => Expertise::firstOrCreate(['name' => $name])->id));

    return $event;
}

/**
 * @param  array<string, mixed>  $query
 */
function listEvents(mixed $test, array $query = []): TestResponse
{
    return $test->getJson('http://test.rimef.localhost/api/events?'.http_build_query($query));
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $this->viewer = agendaMember('Vue', 'Lectrice');
    $this->actingAs($this->viewer);
});

it('requires an open session', function () {
    auth()->logout();
    $this->app['auth']->forgetGuards();

    listEvents($this)->assertUnauthorized();
    $this->getJson($this->tenantUrl('/api/events/filters'))->assertUnauthorized();
    $this->getJson($this->tenantUrl('/api/events/calendar?month=2026-11'))->assertUnauthorized();
});

it('splits upcoming, past and my events, an event running today being upcoming', function () {
    agendaEvent('Passé', '2026-09-01 09:00');
    agendaEvent('En cours', '2026-10-10 09:00', ['ends_at' => Carbon::parse('2026-10-12 18:00')]);
    agendaEvent('Bientôt', '2026-10-20 09:00')->participants()->attach($this->viewer);
    agendaEvent('Plus tard', '2026-11-20 09:00');
    agendaEvent('Mon passé', '2026-08-01 09:00')->participants()->attach($this->viewer);

    $titles = fn (array $query) => listEvents($this, $query)->assertOk()->json('data.*.title');

    expect($titles([]))->toBe(['En cours', 'Bientôt', 'Plus tard'])
        ->and($titles(['period' => 'past']))->toBe(['Passé', 'Mon passé'])
        ->and($titles(['period' => 'mine']))->toBe(['Mon passé', 'Bientôt']);
});

it('describes an event with its first three mediators and their number', function () {
    $event = agendaEvent('Paris Peace Forum', '2026-11-12 08:00', [
        'type' => EventType::InternationalForum,
        'ends_at' => Carbon::parse('2026-11-13 17:00'),
        'city' => 'Paris',
        'country_code' => 'FR',
    ], ['Multilatéralisme']);
    $adminOnly = User::factory()->create();
    $adminOnly->assignRole(Role::Admin);
    $event->participants()->attach([
        agendaMember('Aminata', 'Diallo')->id,
        agendaMember('Claire', 'Dubois')->id,
        agendaMember('Fatou', 'Ndiaye')->id,
        agendaMember('Leïla', 'Bouzid')->id,
        $adminOnly->id,
        $this->viewer->id,
    ]);

    $response = listEvents($this)->assertOk();

    $response->assertJsonPath('data.0.title', 'Paris Peace Forum')
        ->assertJsonPath('data.0.slug', 'paris-peace-forum-2026')
        ->assertJsonPath('data.0.type', ['value' => 'international_forum', 'label' => 'Forum international'])
        ->assertJsonPath('data.0.place', 'Paris, France')
        ->assertJsonPath('data.0.is_online', false)
        ->assertJsonPath('data.0.is_past', false)
        ->assertJsonPath('data.0.themes', ['Multilatéralisme'])
        ->assertJsonPath('data.0.attendee_count', 5)
        ->assertJsonPath('data.0.is_participating', true)
        ->assertJsonCount(3, 'data.0.attendees');
});

it('filters by type, region (online included), theme, month and day', function () {
    agendaEvent('Forum de Paris', '2026-11-12 09:00', ['type' => EventType::InternationalForum, 'country_code' => 'FR'], ['Multilatéralisme']);
    agendaEvent('Atelier de Dakar', '2026-10-26 09:00', ['type' => EventType::Workshop, 'country_code' => 'SN'], ['Médiation communautaire']);
    agendaEvent('Formation en ligne', '2026-11-04 13:00', ['type' => EventType::Training, 'city' => null, 'country_code' => null, 'is_online' => true]);
    agendaEvent('Sommet de Kigali', '2026-11-30 09:00', ['type' => EventType::Summit, 'country_code' => 'RW', 'ends_at' => Carbon::parse('2026-12-01 17:00')]);

    $titles = fn (array $query) => listEvents($this, $query)->assertOk()->json('data.*.title');
    $theme = Expertise::where('name', 'Multilatéralisme')->value('id');

    expect($titles(['type' => ['workshop', 'summit']]))->toBe(['Atelier de Dakar', 'Sommet de Kigali'])
        ->and($titles(['region' => ['west_africa', 'online']]))->toBe(['Atelier de Dakar', 'Formation en ligne'])
        ->and($titles(['theme' => [$theme]]))->toBe(['Forum de Paris'])
        ->and($titles(['month' => '2026-12']))->toBe(['Sommet de Kigali'])
        ->and($titles(['day' => '2026-11-04']))->toBe(['Formation en ligne'])
        ->and($titles(['online' => 1]))->toBe(['Formation en ligne']);
});

it('reads months and days in the time zone of the viewer', function () {
    // 1er décembre à 0 h 30 à Paris = 30 novembre à 23 h 30 UTC
    agendaEvent('Minuit à Paris', '2026-11-30 23:30');

    $titles = fn (array $query) => listEvents($this, $query)->assertOk()->json('data.*.title');

    expect($titles(['month' => '2026-11']))->toBe(['Minuit à Paris'])
        ->and($titles(['month' => '2026-11', 'timezone' => 'Europe/Paris']))->toBe([])
        ->and($titles(['day' => '2026-12-01', 'timezone' => 'Europe/Paris']))->toBe(['Minuit à Paris']);
});

it('keeps crowded events, and searches titles, places, themes and mediators', function () {
    $crowded = agendaEvent('Grand forum', '2026-11-12 09:00', ['city' => 'Paris', 'country_code' => 'FR']);
    $crowded->participants()->attach(collect(range(1, 5))->map(fn (int $i) => agendaMember("Médiatrice{$i}")->id));
    $small = agendaEvent('Petit atelier', '2026-10-20 09:00', ['city' => 'Dakar', 'country_code' => 'SN'], ['Médiation communautaire']);
    $small->participants()->attach([$this->viewer->id, agendaMember('Aminata', 'Diallo')->id]);

    $titles = fn (array $query) => listEvents($this, $query)->assertOk()->json('data.*.title');

    expect($titles(['crowd' => 1]))->toBe(['Grand forum'])
        ->and($titles(['q' => 'senegal']))->toBe(['Petit atelier'])
        ->and($titles(['q' => 'communautaire']))->toBe(['Petit atelier'])
        ->and($titles(['q' => 'diallo']))->toBe(['Petit atelier'])
        ->and($titles(['q' => 'forum paris']))->toBe(['Grand forum'])
        ->and($titles(['sort' => 'attendees']))->toBe(['Grand forum', 'Petit atelier']);
});

it('keeps events with my favorites, in my intervention zones or on my themes', function () {
    $profile = MemberProfile::factory()->for($this->viewer)->create(['country_code' => 'FR']);
    $profile->zones()->create(['country_code' => 'SN', 'position' => 0]);
    $profile->expertises()->attach(Expertise::firstOrCreate(['name' => 'Gouvernance locale']), ['position' => 0]);
    $aminata = agendaMember('Aminata', 'Diallo');
    $this->viewer->favorites()->attach($aminata);

    agendaEvent('Forum de Paris', '2026-11-12 09:00', ['country_code' => 'FR'])->participants()->attach($aminata);
    agendaEvent('Atelier de Dakar', '2026-10-26 09:00', ['country_code' => 'SN']);
    agendaEvent('Atelier de Rabat', '2026-12-05 09:00', ['country_code' => 'MA'], ['Gouvernance locale']);

    $titles = fn (array $query) => listEvents($this, $query)->assertOk()->json('data.*.title');

    expect($titles(['favorites' => 1]))->toBe(['Forum de Paris'])
        ->and($titles(['my_zones' => 1]))->toBe(['Atelier de Dakar'])
        ->and($titles(['my_themes' => 1]))->toBe(['Atelier de Rabat']);

    $this->getJson($this->tenantUrl('/api/events/filters'))
        ->assertJsonPath('data.toggles', ['my_zones' => true, 'my_themes' => true]);
});

it('offers the personal criteria only to a profile that fills them in', function () {
    $this->getJson($this->tenantUrl('/api/events/filters'))
        ->assertJsonPath('data.toggles', ['my_zones' => false, 'my_themes' => false]);
});

it('paginates by 10', function () {
    foreach (range(1, 11) as $day) {
        agendaEvent("Événement {$day}", "2026-11-{$day} 09:00");
    }

    listEvents($this)
        ->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.total', 11)
        ->assertJsonPath('meta.last_page', 2);
});

it('paginates by 20 or 50 on request, and nothing else', function () {
    foreach (range(1, 21) as $day) {
        agendaEvent("Événement {$day}", "2026-11-{$day} 09:00");
    }

    listEvents($this, ['per_page' => 20])
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.last_page', 2);

    listEvents($this, ['per_page' => 50])
        ->assertOk()
        ->assertJsonCount(21, 'data')
        ->assertJsonPath('meta.last_page', 1);

    listEvents($this, ['per_page' => 1000])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});

it('rejects unknown filter values', function () {
    listEvents($this, ['period' => 'demain', 'type' => ['fete'], 'region' => ['atlantide'], 'month' => 'novembre', 'timezone' => 'Mars/Olympus'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['period', 'type.0', 'region.0', 'month', 'timezone']);
});

it('lists the filter values of a tab with their counts, and the size of each tab', function () {
    agendaEvent('Forum de Paris', '2026-11-12 09:00', ['type' => EventType::InternationalForum, 'country_code' => 'FR'], ['Multilatéralisme']);
    agendaEvent('Atelier de Dakar', '2026-11-26 09:00', ['type' => EventType::Workshop, 'country_code' => 'SN'], ['Multilatéralisme', 'Médiation communautaire']);
    agendaEvent('Formation en ligne', '2026-12-04 13:00', ['type' => EventType::Workshop, 'city' => null, 'country_code' => null, 'is_online' => true]);
    agendaEvent('Passé', '2026-09-01 09:00', ['type' => EventType::Summit])->participants()->attach($this->viewer);

    $this->getJson($this->tenantUrl('/api/events/filters'))
        ->assertOk()
        ->assertJsonPath('data.type', [
            ['value' => 'workshop', 'label' => 'Atelier', 'count' => 2],
            ['value' => 'international_forum', 'label' => 'Forum international', 'count' => 1],
        ])
        ->assertJsonPath('data.region', [
            ['value' => 'west_africa', 'label' => 'Afrique de l’Ouest', 'count' => 1],
            ['value' => 'online', 'label' => 'En ligne', 'count' => 1],
            ['value' => 'europe', 'label' => 'Europe', 'count' => 1],
        ])
        ->assertJsonPath('data.theme.*.label', ['Médiation communautaire', 'Multilatéralisme'])
        ->assertJsonPath('data.theme.1.count', 2)
        ->assertJsonPath('data.month', [
            ['value' => '2026-11', 'label' => 'Novembre 2026', 'count' => 2],
            ['value' => '2026-12', 'label' => 'Décembre 2026', 'count' => 1],
        ])
        ->assertJsonPath('data.periods', ['upcoming' => 3, 'past' => 1, 'mine' => 1]);

    $this->getJson($this->tenantUrl('/api/events/filters?period=past'))
        ->assertJsonPath('data.type', [['value' => 'summit', 'label' => 'Sommet', 'count' => 1]]);
});

it('gives the days of a month that have events, events over several days included', function () {
    agendaEvent('Paris Peace Forum', '2026-11-12 08:00', ['ends_at' => Carbon::parse('2026-11-13 17:00')]);
    agendaEvent('Atelier', '2026-11-12 14:00');
    agendaEvent('Sommet', '2026-11-30 09:00', ['ends_at' => Carbon::parse('2026-12-01 17:00')]);
    agendaEvent('Hors du mois', '2026-10-20 09:00');

    $this->getJson($this->tenantUrl('/api/events/calendar?month=2026-11'))
        ->assertOk()
        ->assertJsonPath('data.*.date', ['2026-11-12', '2026-11-13', '2026-11-30'])
        ->assertJsonPath('data.0.events.*.title', ['Paris Peace Forum', 'Atelier'])
        ->assertJsonPath('data.1.events.*.title', ['Paris Peace Forum']);

    $this->getJson($this->tenantUrl('/api/events/calendar'))->assertUnprocessable();
});

it('refuses to join a past event', function () {
    $past = agendaEvent('Passé', '2026-09-01 09:00');

    $this->putJson($this->tenantUrl("/api/events/{$past->id}/participation"))->assertUnprocessable();
    expect($past->participants()->count())->toBe(0);
});
