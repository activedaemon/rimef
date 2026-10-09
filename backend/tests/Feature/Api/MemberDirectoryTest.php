<?php

use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\Expertise;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

/**
 * Médiatrice (rôle member) avec, si un pays est donné, un profil rempli.
 *
 * @param  list<string>  $expertises
 * @param  list<string>  $languages
 */
function mediator(
    string $firstName,
    string $lastName,
    ?string $country = null,
    ?OrganizationType $organization = null,
    array $expertises = [],
    array $languages = [],
    bool $available = false,
): User {
    $user = User::factory()->create(['first_name' => $firstName, 'last_name' => $lastName]);
    $user->assignRole(Role::Member);

    if ($country !== null) {
        $profile = MemberProfile::factory()->for($user)->speaking(...$languages)->create([
            'country_code' => $country,
            'organization_type' => $organization ?? OrganizationType::CivilSociety,
            'is_available' => $available,
        ]);

        foreach ($expertises as $position => $name) {
            $profile->expertises()->attach(
                Expertise::firstOrCreate(['name' => $name]),
                ['position' => $position],
            );
        }
    }

    return $user;
}

/**
 * @param  array<string, mixed>  $query
 */
function listMembers(mixed $test, array $query = []): TestResponse
{
    return $test->getJson('http://test.rimef.localhost/api/members?'.http_build_query($query));
}

beforeEach(function () {
    $this->viewer = mediator('Vue', 'Zz-Lectrice');
    $this->actingAs($this->viewer);
});

it('requires an open session', function () {
    auth()->logout();
    $this->app['auth']->forgetGuards();

    $this->getJson($this->tenantUrl('/api/members'))->assertUnauthorized();
    $this->getJson($this->tenantUrl('/api/members/filters'))->assertUnauthorized();
});

it('lists active mediators only, admins included when they are also members', function () {
    mediator('Aminata', 'Diallo', 'SN');
    mediator('Isabelle', 'Moreau', 'FR')->assignRole(Role::Admin);
    User::factory()->create(['last_name' => 'Seulement-Admin'])->assignRole(Role::Admin);
    mediator('Désactivée', 'Compte', 'FR')->update(['is_active' => false]);

    listMembers($this)
        ->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.directory_total', 3)
        ->assertJsonPath('data.*.name', ['Aminata Diallo', 'Isabelle Moreau', 'Vue Zz-Lectrice']);
});

it('describes a mediator card', function () {
    mediator('Aminata', 'Diallo', 'SN', OrganizationType::CivilSociety, ['Médiation communautaire', 'Femmes, paix et sécurité'], ['fr', 'wo'], true);

    listMembers($this, ['q' => 'Aminata'])
        ->assertOk()
        ->assertJsonPath('data.0', [
            'id' => User::where('last_name', 'Diallo')->value('id'),
            'name' => 'Aminata Diallo',
            'country' => ['code' => 'SN', 'name' => 'Sénégal'],
            'region' => 'Afrique de l’Ouest',
            'organization' => 'Société civile',
            'is_available' => true,
            'expertises' => ['Médiation communautaire', 'Femmes, paix et sécurité'],
            'languages' => [['code' => 'fr', 'name' => 'Français'], ['code' => 'wo', 'name' => 'Wolof']],
        ]);
});

it('shows a mediator without a profile with her name only', function () {
    listMembers($this)
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Vue Zz-Lectrice')
        ->assertJsonPath('data.0.country', null)
        ->assertJsonPath('data.0.expertises', []);
});

it('filters by expertise, region, language, organization and availability', function () {
    $justice = Expertise::firstOrCreate(['name' => 'Médiation et justice']);
    mediator('Mariam', 'Keita', 'ML', OrganizationType::CivilSociety, ['Médiation et justice'], ['fr', 'bm'], true);
    mediator('Claire', 'Dubois', 'FR', OrganizationType::Diplomacy, ['Multilatéralisme'], ['fr', 'en']);
    mediator('Leïla', 'Bouzid', 'MA', OrganizationType::LocalAuthority, ['Gouvernance locale'], ['fr', 'ar']);

    $names = fn (array $query) => listMembers($this, $query)->assertOk()->json('data.*.name');

    expect($names(['expertise' => [$justice->id]]))->toBe(['Mariam Keita'])
        ->and($names(['region' => ['west_africa', 'north_africa']]))->toBe(['Leïla Bouzid', 'Mariam Keita'])
        ->and($names(['language' => ['en']]))->toBe(['Claire Dubois'])
        ->and($names(['organization' => ['diplomacy', 'local_authority']]))->toBe(['Leïla Bouzid', 'Claire Dubois'])
        ->and($names(['available' => 1]))->toBe(['Mariam Keita'])
        ->and($names(['region' => ['europe'], 'language' => ['ar']]))->toBe([]);
});

it('searches names, countries, expertises and languages, accents ignored', function () {
    mediator('Aminata', 'Diallo', 'SN', expertises: ['Médiation communautaire'], languages: ['wo']);
    mediator('Claire', 'Dubois', 'FR', expertises: ['Multilatéralisme'], languages: ['en']);

    $names = fn (string $q) => listMembers($this, ['q' => $q])->assertOk()->json('data.*.name');

    expect($names('dubois'))->toBe(['Claire Dubois'])
        ->and($names('senegal'))->toBe(['Aminata Diallo'])
        ->and($names('Multilat'))->toBe(['Claire Dubois'])
        ->and($names('wolof'))->toBe(['Aminata Diallo'])
        ->and($names('Claire France'))->toBe(['Claire Dubois'])
        ->and($names('%'))->toBe([]);
});

it('sorts by country name, mediators without a country last', function () {
    mediator('Sarah', 'Mensah', 'CA');
    mediator('Andrée', 'Niyonsaba', 'BI');
    mediator('Leïla', 'Bouzid', 'MA');

    listMembers($this, ['sort' => 'country'])
        ->assertOk()
        ->assertJsonPath('data.*.name', ['Andrée Niyonsaba', 'Sarah Mensah', 'Leïla Bouzid', 'Vue Zz-Lectrice']);
});

it('paginates by 12 and keeps the total of the directory', function () {
    foreach (range(1, 13) as $i) {
        mediator('Médiatrice', "Nom{$i}", 'FR', languages: ['fr']);
    }

    listMembers($this, ['language' => ['fr']])
        ->assertOk()
        ->assertJsonCount(12, 'data')
        ->assertJsonPath('meta.total', 13)
        ->assertJsonPath('meta.last_page', 2)
        ->assertJsonPath('meta.directory_total', 14);
});

it('rejects unknown filter values', function () {
    listMembers($this, ['region' => ['atlantide'], 'sort' => 'age'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['region.0', 'sort']);
});

it('lists the filter values carried by mediators with their counts', function () {
    mediator('Aminata', 'Diallo', 'SN', OrganizationType::CivilSociety, ['Médiation communautaire'], ['fr', 'wo']);
    mediator('Mariam', 'Keita', 'ML', OrganizationType::CivilSociety, ['Médiation communautaire'], ['fr']);
    mediator('Claire', 'Dubois', 'FR', OrganizationType::Diplomacy, ['Multilatéralisme'], ['fr']);
    Expertise::firstOrCreate(['name' => 'Sans médiatrice']);

    $this->getJson($this->tenantUrl('/api/members/filters'))
        ->assertOk()
        ->assertJsonPath('data.expertise.*.label', ['Médiation communautaire', 'Multilatéralisme'])
        ->assertJsonPath('data.expertise.0.count', 2)
        ->assertJsonPath('data.region', [
            ['value' => 'west_africa', 'label' => 'Afrique de l’Ouest', 'count' => 2],
            ['value' => 'europe', 'label' => 'Europe', 'count' => 1],
        ])
        ->assertJsonPath('data.language', [
            ['value' => 'fr', 'label' => 'Français', 'count' => 3],
            ['value' => 'wo', 'label' => 'Wolof', 'count' => 1],
        ])
        ->assertJsonPath('data.organization', [
            ['value' => 'diplomacy', 'label' => 'Diplomatie', 'count' => 1],
            ['value' => 'civil_society', 'label' => 'Société civile', 'count' => 2],
        ]);
});
