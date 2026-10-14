<?php

use App\Enums\EventRole;
use App\Enums\OrganizationType;
use App\Enums\ResourceMemberRole;
use App\Enums\ResourceType;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\MemberProfile;
use App\Models\Resource;
use App\Models\ResourceRecommendation;
use App\Models\User;
use App\Services\Messaging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 10));

    $this->viewer = User::factory()->create();
    $this->viewer->assignRole(Role::Member);

    $this->mediator = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo']);
    $this->mediator->assignRole(Role::Member);
    $this->profile = MemberProfile::factory()->for($this->mediator)->create([
        'country_code' => 'SN',
        'city' => 'Dakar',
        'organization_type' => OrganizationType::CivilSociety,
        'job_title' => 'Conseillère en paix et sécurité',
        'tagline' => 'Favoriser le dialogue inclusif.',
        'bio' => 'Médiatrice en Afrique de l’Ouest.',
        'experience_since' => 2011,
        'audiences' => 'Femmes, jeunes',
    ]);
    $this->profile->zones()->createMany([
        ['country_code' => 'ML', 'position' => 1],
        ['country_code' => 'SN', 'position' => 0],
    ]);
    $this->profile->expertises()->attach(Expertise::firstOrCreate(['name' => 'Médiation communautaire']), ['position' => 0]);
});

it('requires an open session', function () {
    $this->getJson($this->tenantUrl('/api/members/aminata-diallo'))->assertUnauthorized();
});

it('shows the profile of a mediator found by her slug', function () {
    $this->actingAs($this->viewer)
        ->getJson($this->tenantUrl('/api/members/aminata-diallo'))
        ->assertOk()
        ->assertJson(['data' => [
            'id' => $this->mediator->id,
            'slug' => 'aminata-diallo',
            'name' => 'Aminata Diallo',
            'first_name' => 'Aminata',
            'country' => ['code' => 'SN', 'name' => 'Sénégal'],
            'city' => 'Dakar',
            'organization' => 'Société civile',
            'job_title' => 'Conseillère en paix et sécurité',
            'tagline' => 'Favoriser le dialogue inclusif.',
            'bio' => 'Médiatrice en Afrique de l’Ouest.',
            'years_of_experience' => 15,
            'audiences' => 'Femmes, jeunes',
            'is_favorite' => false,
            'conversation' => null,
            'expertises' => ['Médiation communautaire'],
            'zones' => [['code' => 'SN', 'name' => 'Sénégal'], ['code' => 'ML', 'name' => 'Mali']],
        ]]);
});

it('tells whether the mediator is in the favorites of the viewer', function () {
    $this->viewer->favorites()->attach($this->mediator);

    $this->actingAs($this->viewer)
        ->getJson($this->tenantUrl('/api/members/aminata-diallo'))
        ->assertJsonPath('data.is_favorite', true);
});

it('gives the conversation with the viewer and its unread messages', function () {
    $messaging = app(Messaging::class);
    $conversation = $messaging->conversationBetween($this->viewer, $this->mediator);
    $messaging->send($conversation, $this->viewer, 'Bonjour Aminata');
    $messaging->send($conversation, $this->mediator, 'Bonjour !');
    $messaging->send($conversation, $this->mediator, 'Avec plaisir.');

    $this->actingAs($this->viewer)
        ->getJson($this->tenantUrl('/api/members/aminata-diallo'))
        ->assertJsonPath('data.conversation', ['id' => $conversation->id, 'unread_count' => 2]);

    $messaging->markAsRead($conversation, $this->viewer);
    $this->getJson($this->tenantUrl('/api/members/aminata-diallo'))
        ->assertJsonPath('data.conversation.unread_count', 0);

    // Sa propre fiche : pas d'onglet Messages
    $this->getJson($this->tenantUrl("/api/members/{$this->viewer->slug}"))
        ->assertJsonPath('data.conversation', null);
});

it('shows only the identity of a mediator without a profile', function () {
    $mediator = User::factory()->create(['first_name' => 'Mireille', 'last_name' => 'Kouassi']);
    $mediator->assignRole(Role::Member);

    $this->actingAs($this->viewer)
        ->getJson($this->tenantUrl('/api/members/mireille-kouassi'))
        ->assertOk()
        ->assertJsonPath('data.name', 'Mireille Kouassi')
        ->assertJsonPath('data.country', null)
        ->assertJsonPath('data.years_of_experience', null)
        ->assertJsonPath('data.zones', []);
});

it('returns 404 for an unknown slug or an account outside the directory', function () {
    $inactive = User::factory()->inactive()->create(['first_name' => 'Compte', 'last_name' => 'Inactif']);
    $inactive->assignRole(Role::Member);
    $adminOnly = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'Seule']);
    $adminOnly->assignRole(Role::Admin);

    $this->actingAs($this->viewer);
    $this->getJson($this->tenantUrl('/api/members/inconnue'))->assertNotFound();
    $this->getJson($this->tenantUrl('/api/members/compte-inactif'))->assertNotFound();
    $this->getJson($this->tenantUrl('/api/members/admin-seule'))->assertNotFound();
});

it('loads the profile with a fixed number of queries', function () {
    $this->actingAs($this->viewer);
    $this->getJson($this->tenantUrl('/api/members/aminata-diallo'))->assertOk();

    DB::enableQueryLog();
    $this->getJson($this->tenantUrl('/api/members/aminata-diallo'))->assertOk();
    $queries = count(DB::getQueryLog());

    $this->profile->zones()->createMany([['country_code' => 'MR', 'position' => 2], ['country_code' => 'CI', 'position' => 3]]);
    $this->profile->expertises()->attach(Expertise::firstOrCreate(['name' => 'Gouvernance locale']), ['position' => 1]);

    DB::flushQueryLog();
    $this->getJson($this->tenantUrl('/api/members/aminata-diallo'))->assertOk();

    expect(count(DB::getQueryLog()))->toBe($queries);
});

it('gives the slug in the directory and in the current user', function () {
    $this->actingAs($this->viewer);

    $this->getJson($this->tenantUrl('/api/members?q=Aminata'))->assertJsonPath('data.0.slug', 'aminata-diallo');
    $this->getJson($this->tenantUrl('/api/user'))->assertJsonPath('data.slug', $this->viewer->slug);
});

it('gives the next three events of the mediator, with her role, past events excluded', function () {
    $passed = Event::factory()->create(['title' => 'Passé', 'starts_at' => now()->subWeek()]);
    $later = Event::factory()->create(['title' => 'Plus tard', 'starts_at' => now()->addMonths(3)]);
    $forum = Event::factory()->create(['title' => 'Forum', 'summary' => 'Un forum.', 'starts_at' => now()->addWeeks(2), 'city' => 'Paris', 'country_code' => 'FR']);
    $soon = Event::factory()->create(['title' => 'Bientôt', 'starts_at' => now()->addWeek()]);
    $fourth = Event::factory()->create(['title' => 'Quatrième', 'starts_at' => now()->addMonths(6)]);
    $notHers = Event::factory()->create(['title' => 'Sans elle', 'starts_at' => now()->addDay()]);

    $this->mediator->events()->attach([$passed->id, $later->id, $soon->id, $fourth->id]);
    $this->mediator->events()->attach($forum, ['role' => EventRole::Speaker->value]);

    $response = $this->actingAs($this->viewer)
        ->getJson($this->tenantUrl('/api/members/aminata-diallo'))
        ->assertOk();

    expect(array_column($response->json('data.upcoming_events'), 'title'))->toBe(['Bientôt', 'Forum', 'Plus tard'])
        ->and($response->json('data.upcoming_events.1'))->toMatchArray([
            'slug' => $forum->slug,
            'summary' => 'Un forum.',
            'place' => 'Paris, France',
            'role' => ['value' => 'speaker', 'label' => 'Intervenante'],
        ])
        ->and($response->json('data.upcoming_events.0.role.label'))->toBe('Participante');
});

it('lists the contributions of a mediator, with her part and the related event', function () {
    $forum = Event::factory()->create(['title' => 'Paris Peace Forum']);
    Resource::factory()->create(['title' => 'Écrite', 'type' => ResourceType::Article, 'source' => null, 'author_id' => $this->mediator->id, 'published_on' => '2025-11-18']);
    Resource::factory()->create(['title' => 'Contribution', 'type' => ResourceType::Report, 'published_on' => '2026-04-14', 'event_id' => $forum->id])
        ->members()->attach($this->mediator, ['role' => ResourceMemberRole::Contributor->value]);
    Resource::factory()->create(['title' => 'Citée', 'type' => ResourceType::Podcast, 'published_on' => '2025-12-09', 'duration_minutes' => 34])
        ->members()->attach($this->mediator, ['role' => ResourceMemberRole::Mentioned->value]);
    $recommended = Resource::factory()->create(['title' => 'Recommandée', 'published_on' => '2026-05-01']);
    ResourceRecommendation::query()->create(['resource_id' => $recommended->id, 'user_id' => $this->mediator->id, 'quote' => 'À lire.']);
    Resource::factory()->create(['title' => 'Sans elle', 'published_on' => '2026-06-01']);

    $this->actingAs($this->viewer)
        ->getJson($this->tenantUrl('/api/members/aminata-diallo'))
        ->assertOk()
        ->assertJsonPath('data.resources.*.title', ['Contribution', 'Citée', 'Écrite'])
        ->assertJsonPath('data.resources.0.role', ['value' => 'contributor', 'label' => 'Contributrice'])
        ->assertJsonPath('data.resources.0.type', ['value' => 'report', 'label' => 'Rapport', 'action' => 'Lire'])
        ->assertJsonPath('data.resources.0.event', ['slug' => $forum->slug, 'title' => 'Paris Peace Forum'])
        ->assertJsonPath('data.resources.1.role.label', 'Citée')
        ->assertJsonPath('data.resources.1.duration_minutes', 34)
        ->assertJsonPath('data.resources.2.role', ['value' => 'author', 'label' => 'Autrice'])
        ->assertJsonPath('data.resources.2.event', null);
});
