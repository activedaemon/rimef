<?php

use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\Expertise;
use App\Models\MemberProfile;
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
            'region' => 'Afrique de l’Ouest',
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
