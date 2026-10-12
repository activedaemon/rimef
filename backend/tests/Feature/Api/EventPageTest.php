<?php

use App\Enums\EventRole;
use App\Enums\EventType;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

function pageMember(string $firstName, string $lastName, ?string $country = null): User
{
    $user = User::factory()->create(['first_name' => $firstName, 'last_name' => $lastName]);
    $user->assignRole(Role::Member);
    MemberProfile::factory()->for($user)->create(['country_code' => $country]);

    return $user;
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-11 12:00:00'));
    $this->viewer = pageMember('Vue', 'Lectrice');
    $this->actingAs($this->viewer);

    $this->event = Event::factory()->create([
        'title' => 'Paris Peace Forum',
        'type' => EventType::InternationalForum,
        'starts_at' => Carbon::parse('2026-11-10 08:00:00'),
        'ends_at' => Carbon::parse('2026-11-11 17:00:00'),
        'city' => 'Paris',
        'venue' => 'Palais de Chaillot',
        'country_code' => 'FR',
        'description' => 'Un forum.',
        'organizer_name' => 'Forum de Paris sur la Paix',
        'organizer_detail' => 'Association loi 1901, Paris',
        'links' => [['label' => 'Site officiel', 'url' => 'https://parispeaceforum.org/fr/']],
    ]);
});

it('requires an open session', function () {
    auth()->logout();
    $this->app['auth']->forgetGuards();

    $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026'))->assertUnauthorized();
    $this->get($this->tenantUrl("/api/events/{$this->event->id}/photo"))->assertUnauthorized();
});

it('shows an event by its slug, with its practical details', function () {
    $this->event->themes()->attach(Expertise::firstOrCreate(['name' => 'Multilatéralisme']));

    $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026'))
        ->assertOk()
        ->assertJsonPath('data.title', 'Paris Peace Forum')
        ->assertJsonPath('data.status', 'upcoming')
        ->assertJsonPath('data.venue', 'Palais de Chaillot')
        ->assertJsonPath('data.place', 'Paris, France')
        ->assertJsonPath('data.organizer', ['name' => 'Forum de Paris sur la Paix', 'detail' => 'Association loi 1901, Paris'])
        ->assertJsonPath('data.links.0.url', 'https://parispeaceforum.org/fr/')
        ->assertJsonPath('data.themes', ['Multilatéralisme'])
        ->assertJsonPath('data.photo_url', null);
});

it('returns 404 for an unknown event', function () {
    $this->getJson($this->tenantUrl('/api/events/evenement-inconnu-2026'))->assertNotFound();
});

it('gives the status of an ongoing or past event', function () {
    $this->travelTo(Carbon::parse('2026-11-10 12:00:00'));
    $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026'))->assertJsonPath('data.status', 'ongoing');

    $this->travelTo(Carbon::parse('2026-11-12 12:00:00'));
    $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026'))->assertJsonPath('data.status', 'past');
});

it('lists every listed attendee, speakers first, with role, note, country and expertises', function () {
    $speaker = pageMember('Aminata', 'Diallo', 'SN');
    $speaker->memberProfile->expertises()->attach(Expertise::firstOrCreate(['name' => 'Médiation communautaire']), ['position' => 0]);
    $participant = pageMember('Claire', 'Bernard', 'FR');
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin);

    $this->event->participants()->attach($participant, ['attendance_note' => 'Présente le 10 novembre']);
    $this->event->participants()->attach($speaker, ['role' => EventRole::Speaker->value]);
    $this->event->participants()->attach([$admin->id, $this->viewer->id]);

    $response = $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026'))->assertOk();

    expect($response->json('data.attendee_count'))->toBe(3)
        ->and($response->json('data.is_participating'))->toBeTrue()
        ->and(array_column($response->json('data.attendees'), 'name'))->toBe(['Aminata Diallo', 'Claire Bernard', 'Vue Lectrice'])
        ->and($response->json('data.attendees.0'))->toMatchArray([
            'country' => 'Sénégal',
            'expertises' => ['Médiation communautaire'],
            'role' => ['value' => 'speaker', 'label' => 'Intervenante'],
            'attendance_note' => null,
        ])
        ->and($response->json('data.attendees.1.role.label'))->toBe('Participante')
        ->and($response->json('data.attendees.1.attendance_note'))->toBe('Présente le 10 novembre');
});

it('groups the program by day in the timezone of the viewer, with listed speakers only', function () {
    $speaker = pageMember('Aminata', 'Diallo');
    $inactive = pageMember('Ancienne', 'Membre');
    $inactive->update(['is_active' => false]);

    // 23:30 UTC le 10 = 00:30 le 11 à Paris
    $this->event->sessions()->createMany([
        ['starts_at' => Carbon::parse('2026-11-10 23:30:00'), 'title' => 'Nocturne', 'speaker_id' => $inactive->id],
        ['starts_at' => Carbon::parse('2026-11-10 13:30:00'), 'title' => 'Panel', 'speaker_id' => $speaker->id, 'is_network' => true],
        ['starts_at' => Carbon::parse('2026-11-10 08:00:00'), 'title' => 'Ouverture', 'detail' => 'Salle plénière'],
    ]);

    $program = $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026?timezone=Europe/Paris'))
        ->assertOk()
        ->json('data.program');

    expect(array_column($program, 'date'))->toBe(['2026-11-10', '2026-11-11'])
        ->and(array_column($program[0]['sessions'], 'time'))->toBe(['09:00', '14:30'])
        ->and($program[0]['sessions'][0]['detail'])->toBe('Salle plénière')
        ->and($program[0]['sessions'][1]['is_network'])->toBeTrue()
        ->and($program[0]['sessions'][1]['speaker']['slug'])->toBe('aminata-diallo')
        ->and($program[1]['sessions'][0]['time'])->toBe('00:30')
        ->and($program[1]['sessions'][0]['speaker'])->toBeNull();
});

it('rejects an unknown timezone', function () {
    $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026?timezone=Mars/Olympus'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('timezone');
});

it('serves the photo of the event with a long private cache, and gives its versioned url', function () {
    Storage::fake('local');
    Storage::disk('local')->put('events/1.webp', 'image');
    $this->event->update(['photo_path' => 'events/1.webp']);

    $this->get($this->tenantUrl("/api/events/{$this->event->id}/photo"))
        ->assertOk()
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, private');

    $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026'))
        ->assertJsonPath('data.photo_url', "/api/events/{$this->event->id}/photo?v={$this->event->updated_at->getTimestamp()}");
});

it('returns 404 for an event without photo', function () {
    Storage::fake('local');

    $this->get($this->tenantUrl("/api/events/{$this->event->id}/photo"))->assertNotFound();
});

it('makes a new participant a simple participant', function () {
    $this->putJson($this->tenantUrl("/api/events/{$this->event->id}/participation"))->assertNoContent();

    $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026'))
        ->assertJsonPath('data.attendees.0.role.value', 'participant')
        ->assertJsonPath('data.attendees.0.attendance_note', null);
});

it('offers to meet on site, registering the participation if needed, and shows it to the others', function () {
    $other = pageMember('Claire', 'Bernard');
    $this->event->participants()->attach($other);

    $this->putJson($this->tenantUrl("/api/events/{$this->event->id}/meeting"))->assertNoContent();
    $this->putJson($this->tenantUrl("/api/events/{$this->event->id}/meeting"))->assertNoContent();

    $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026'))
        ->assertJsonPath('data.is_participating', true)
        ->assertJsonPath('data.wants_to_meet', true)
        ->assertJsonPath('data.attendees.0.name', 'Claire Bernard')
        ->assertJsonPath('data.attendees.0.wants_to_meet', false)
        ->assertJsonPath('data.attendees.1.wants_to_meet', true);

    $this->deleteJson($this->tenantUrl("/api/events/{$this->event->id}/meeting"))->assertNoContent();

    $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026'))
        ->assertJsonPath('data.is_participating', true)
        ->assertJsonPath('data.wants_to_meet', false);
});

it('drops the offer to meet with the participation, and refuses it on a past event', function () {
    $this->putJson($this->tenantUrl("/api/events/{$this->event->id}/meeting"))->assertNoContent();
    $this->deleteJson($this->tenantUrl("/api/events/{$this->event->id}/participation"))->assertNoContent();
    $this->putJson($this->tenantUrl("/api/events/{$this->event->id}/participation"))->assertNoContent();

    $this->getJson($this->tenantUrl('/api/events/paris-peace-forum-2026'))
        ->assertJsonPath('data.wants_to_meet', false);

    $this->travelTo(Carbon::parse('2026-11-12 12:00:00'));
    $this->putJson($this->tenantUrl("/api/events/{$this->event->id}/meeting"))->assertUnprocessable();
});

it('requires an open session to offer to meet', function () {
    auth()->logout();
    $this->app['auth']->forgetGuards();

    $this->putJson($this->tenantUrl("/api/events/{$this->event->id}/meeting"))->assertUnauthorized();
});
