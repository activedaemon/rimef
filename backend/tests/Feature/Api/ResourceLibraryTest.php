<?php

use App\Enums\Region;
use App\Enums\ResourceMemberRole;
use App\Enums\ResourceType;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\MemberProfile;
use App\Models\Resource;
use App\Models\ResourceRecommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

function libraryMember(string $firstName, string $lastName = 'Médiatrice'): User
{
    $user = User::factory()->create(['first_name' => $firstName, 'last_name' => $lastName]);
    $user->assignRole(Role::Member);

    return $user;
}

/**
 * @param  array<string, mixed>  $attributes
 * @param  list<string>  $themes
 */
function libraryResource(string $title, string $publishedOn, array $attributes = [], array $themes = []): Resource
{
    $resource = Resource::factory()->create(['title' => $title, 'published_on' => $publishedOn, ...$attributes]);
    $resource->themes()->attach(collect($themes)->map(fn (string $name): int => Expertise::firstOrCreate(['name' => $name])->id));

    return $resource;
}

/**
 * @param  array<string, mixed>  $query
 */
function listResources(mixed $test, array $query = []): TestResponse
{
    return $test->getJson('http://test.rimef.localhost/api/resources?'.http_build_query($query));
}

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-14 12:00:00'));
    $this->viewer = libraryMember('Vue', 'Lectrice');
    $this->actingAs($this->viewer);
});

it('requires an open session', function () {
    auth()->logout();
    $this->app['auth']->forgetGuards();

    listResources($this)->assertUnauthorized();
    $this->getJson($this->tenantUrl('/api/resources/filters'))->assertUnauthorized();
    $this->getJson($this->tenantUrl('/api/resources/voices'))->assertUnauthorized();
    $this->getJson($this->tenantUrl('/api/resources/une-ressource'))->assertUnauthorized();
    $this->putJson($this->tenantUrl('/api/resources/1/favorite'))->assertUnauthorized();
});

it('lists the most recent resources first, with their type, source or author, and themes', function () {
    $aminata = libraryMember('Aminata', 'Diallo');
    libraryResource('Bilan annuel', '2025-07-02', ['type' => ResourceType::Report, 'source' => 'RIMeF']);
    libraryResource('Paroles de médiatrices', '2025-12-09', [
        'type' => ResourceType::Podcast,
        'source' => null,
        'author_id' => $aminata->id,
        'duration_minutes' => 34,
    ], ['Femmes, paix et sécurité']);

    listResources($this)
        ->assertOk()
        ->assertJsonPath('data.*.title', ['Paroles de médiatrices', 'Bilan annuel'])
        ->assertJsonPath('data.0.type', ['value' => 'podcast', 'label' => 'Podcast', 'action' => 'Écouter'])
        ->assertJsonPath('data.0.author.name', 'Aminata Diallo')
        ->assertJsonPath('data.0.published_on', '2025-12-09')
        ->assertJsonPath('data.0.duration_minutes', 34)
        ->assertJsonPath('data.0.themes.0.name', 'Femmes, paix et sécurité')
        ->assertJsonPath('data.0.is_favorite', false)
        ->assertJsonPath('data.1.source', 'RIMeF')
        ->assertJsonPath('data.1.author', null);
});

it('sorts by oldest or by title', function () {
    libraryResource('Bêta', '2026-01-10');
    libraryResource('Alpha', '2025-03-10');
    libraryResource('Gamma', '2026-05-10');

    listResources($this, ['sort' => 'oldest'])->assertJsonPath('data.*.title', ['Alpha', 'Bêta', 'Gamma']);
    listResources($this, ['sort' => 'title'])->assertJsonPath('data.*.title', ['Alpha', 'Bêta', 'Gamma']);
    listResources($this)->assertJsonPath('data.*.title', ['Gamma', 'Bêta', 'Alpha']);
});

it('searches every word in the title, source, type, theme or names', function () {
    $mariam = libraryMember('Mariam', 'Keita');
    libraryResource('Justice transitionnelle au Mali', '2025-09-15', ['author_id' => $mariam->id, 'source' => null]);
    libraryResource('Guide pratique', '2025-10-06', ['type' => ResourceType::Tool, 'source' => 'International Alert'], ['Médiation communautaire']);
    libraryResource('Femmes et paix', '2025-11-20', ['type' => ResourceType::Video, 'source' => 'ONU Femmes'])
        ->members()->attach($mariam);

    listResources($this, ['q' => 'keita'])->assertJsonPath('data.*.title', ['Femmes et paix', 'Justice transitionnelle au Mali']);
    listResources($this, ['q' => 'outil communautaire'])->assertJsonPath('data.*.title', ['Guide pratique']);
    listResources($this, ['q' => 'alert'])->assertJsonPath('data.*.title', ['Guide pratique']);
});

it('filters by type, theme, region, language and date', function () {
    libraryResource('Rapport Afrique', '2026-04-14', ['type' => ResourceType::Report, 'region' => Region::WestAfrica, 'languages' => ['fr', 'en']], ['Médiation internationale']);
    libraryResource('Vidéo mondiale', '2025-11-20', ['type' => ResourceType::Video, 'region' => null, 'languages' => ['fr']]);
    libraryResource('Article Europe', '2025-03-01', ['type' => ResourceType::Article, 'region' => Region::Europe, 'languages' => ['en']], ['Multilatéralisme']);

    listResources($this, ['type' => ['report', 'video']])->assertJsonPath('data.*.title', ['Rapport Afrique', 'Vidéo mondiale']);
    listResources($this, ['theme' => [Expertise::where('name', 'Multilatéralisme')->value('id')]])->assertJsonPath('data.*.title', ['Article Europe']);
    listResources($this, ['region' => ['world', 'europe']])->assertJsonPath('data.*.title', ['Vidéo mondiale', 'Article Europe']);
    listResources($this, ['language' => ['en']])->assertJsonPath('data.*.title', ['Rapport Afrique', 'Article Europe']);
    listResources($this, ['date' => 'recent'])->assertJsonPath('data.*.title', ['Rapport Afrique']);
    listResources($this, ['date' => '2025'])->assertJsonPath('data.*.title', ['Vidéo mondiale', 'Article Europe']);
});

it('filters the resources linked to the network, written by a mediator, tied to an event or in her favorites', function () {
    $fatou = libraryMember('Fatou', 'Ndiaye');
    $byMember = libraryResource('Écrite', '2026-03-01', ['author_id' => $fatou->id]);
    libraryResource('Recommandée', '2026-02-01')->members()->attach($fatou);
    libraryResource('Présentée', '2026-01-01', ['event_id' => Event::factory()->create()->id]);
    libraryResource('Seule', '2025-12-01');
    $this->viewer->favoriteResources()->attach($byMember);

    listResources($this, ['linked' => 1])->assertJsonPath('data.*.title', ['Écrite', 'Recommandée']);
    listResources($this, ['by_member' => 1])->assertJsonPath('data.*.title', ['Écrite']);
    listResources($this, ['with_event' => 1])->assertJsonPath('data.*.title', ['Présentée']);
    listResources($this, ['favorites' => 1])
        ->assertJsonPath('data.*.title', ['Écrite'])
        ->assertJsonPath('data.0.is_favorite', true);
});

it('paginates by 9, or 18 and 36 on request, and nothing else', function () {
    foreach (range(1, 19) as $day) {
        libraryResource("Ressource {$day}", "2026-01-{$day}");
    }

    listResources($this)
        ->assertOk()
        ->assertJsonCount(9, 'data')
        ->assertJsonPath('meta.total', 19)
        ->assertJsonPath('meta.last_page', 3);

    listResources($this, ['per_page' => 18])
        ->assertJsonCount(18, 'data')
        ->assertJsonPath('meta.per_page', 18)
        ->assertJsonPath('meta.last_page', 2);

    listResources($this, ['per_page' => 36])
        ->assertJsonCount(19, 'data')
        ->assertJsonPath('meta.last_page', 1);

    listResources($this, ['per_page' => 8])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);

    listResources($this, ['per_page' => 1000])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['per_page']);
});

it('rejects unknown filter values', function () {
    listResources($this, ['type' => ['roman'], 'region' => ['atlantide'], 'language' => ['xx'], 'date' => 'hier', 'sort' => 'hasard'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type.0', 'region.0', 'language.0', 'date', 'sort']);
});

it('lists the filter values with their counts, and the size of the library', function () {
    libraryResource('Rapport Afrique', '2026-04-14', ['type' => ResourceType::Report, 'region' => Region::WestAfrica, 'languages' => ['fr', 'en']], ['Médiation internationale']);
    libraryResource('Vidéo mondiale', '2025-11-20', ['type' => ResourceType::Video, 'region' => null, 'languages' => ['fr']], ['Médiation internationale']);
    Expertise::firstOrCreate(['name' => 'Sans ressource']);

    $this->getJson($this->tenantUrl('/api/resources/filters'))
        ->assertOk()
        ->assertJsonPath('data.type', [
            ['value' => 'report', 'label' => 'Rapport', 'count' => 1],
            ['value' => 'video', 'label' => 'Vidéo', 'count' => 1],
        ])
        ->assertJsonPath('data.theme.*.label', ['Médiation internationale'])
        ->assertJsonPath('data.theme.0.count', 2)
        ->assertJsonPath('data.region', [
            ['value' => 'west_africa', 'label' => 'Afrique de l’Ouest', 'count' => 1],
            ['value' => 'world', 'label' => 'Monde', 'count' => 1],
        ])
        ->assertJsonPath('data.language', [
            ['value' => 'en', 'label' => 'Anglais', 'count' => 1],
            ['value' => 'fr', 'label' => 'Français', 'count' => 2],
        ])
        ->assertJsonPath('data.date', [
            ['value' => 'recent', 'label' => 'Moins de 6 mois', 'count' => 1],
            ['value' => '2026', 'label' => 'Publiées en 2026', 'count' => 1],
            ['value' => '2025', 'label' => 'Publiées en 2025', 'count' => 1],
        ])
        ->assertJsonPath('data.total', 2);
});

it('adds a resource to the favorites and removes it, idempotently', function () {
    $resource = libraryResource('Guide', '2026-01-01');

    $this->putJson($this->tenantUrl("/api/resources/{$resource->id}/favorite"))->assertNoContent();
    $this->putJson($this->tenantUrl("/api/resources/{$resource->id}/favorite"))->assertNoContent();
    expect($this->viewer->favoriteResources()->count())->toBe(1);

    $this->deleteJson($this->tenantUrl("/api/resources/{$resource->id}/favorite"))->assertNoContent();
    $this->deleteJson($this->tenantUrl("/api/resources/{$resource->id}/favorite"))->assertNoContent();
    expect($this->viewer->favoriteResources()->count())->toBe(0);

    $this->putJson($this->tenantUrl('/api/resources/999/favorite'))->assertNotFound();
});

it('gives the latest reviews of active mediators, with the resource and the mediator', function () {
    $fatou = libraryMember('Fatou', 'Ndiaye');
    $profile = MemberProfile::factory()->for($fatou)->create(['country_code' => 'CI']);
    $profile->expertises()->attach(Expertise::firstOrCreate(['name' => 'Prévention des conflits']), ['position' => 1]);
    $aminata = libraryMember('Aminata', 'Diallo');
    $inactive = libraryMember('Ancienne', 'Membre');
    $inactive->forceFill(['is_active' => false])->save();
    $report = libraryResource('Women Mediators', '2026-04-14', ['url' => 'https://www.c-r.org/']);

    $review = fn (User $member, Resource $resource, string $quote, int $daysAgo) => ResourceRecommendation::query()
        ->create(['resource_id' => $resource->id, 'user_id' => $member->id, 'quote' => $quote])
        ->forceFill(['created_at' => now()->subDays($daysAgo)])
        ->save();
    $review($fatou, $report, 'Un rapport précieux.', 1);
    $review($aminata, libraryResource('Paroles', '2025-12-09'), 'Des voix de terrain.', 2);
    $review($aminata, libraryResource('Guide', '2025-10-06'), 'Simple et concret.', 3);
    $review($aminata, libraryResource('Ancien', '2025-01-06'), 'Trop ancien pour le bandeau.', 9);
    $review($inactive, $report, 'Compte désactivé.', 0);

    $this->getJson($this->tenantUrl('/api/resources/voices'))
        ->assertOk()
        ->assertJsonPath('data.*.quote', ['Un rapport précieux.', 'Des voix de terrain.', 'Simple et concret.'])
        ->assertJsonPath('data.0.resource.title', 'Women Mediators')
        ->assertJsonPath('data.0.resource.url', 'https://www.c-r.org/')
        ->assertJsonPath('data.0.member.name', 'Fatou Ndiaye')
        ->assertJsonPath('data.0.member.country', 'Côte d’Ivoire')
        ->assertJsonPath('data.0.member.expertise', 'Prévention des conflits')
        ->assertJsonPath('data.1.member.country', null)
        ->assertJsonPath('data.1.member.expertise', null);
});

it('shows the page of a resource with its organization, mediators, event and related resources', function () {
    $aminata = libraryMember('Aminata', 'Diallo');
    MemberProfile::factory()->for($aminata)->create(['country_code' => 'SN']);
    $fatou = libraryMember('Fatou', 'Ndiaye');
    $inactive = libraryMember('Ancienne', 'Membre');
    $inactive->forceFill(['is_active' => false])->save();
    $forum = Event::factory()->create(['title' => 'Paris Peace Forum', 'city' => 'Paris', 'country_code' => 'FR', 'starts_at' => now()->addMonth()]);
    $forum->participants()->attach([$aminata->id, $fatou->id, $inactive->id]);

    $report = libraryResource('Women Mediators', '2026-04-14', [
        'type' => ResourceType::Report,
        'summary' => 'Un rapport sur les femmes médiatrices.',
        'source' => 'Conciliation Resources',
        'source_detail' => 'Organisation basée à Londres.',
        'source_url' => 'https://www.c-r.org/',
        'pages' => 42,
        'region' => Region::WestAfrica,
        'languages' => ['fr', 'en'],
        'event_id' => $forum->id,
        'event_note' => 'Présenté au panel du forum.',
    ], ['Femmes, paix et sécurité']);
    $report->members()->attach($aminata, ['role' => ResourceMemberRole::Contributor->value, 'note' => 'Citée au chapitre 3.']);
    $report->members()->attach($inactive, ['role' => ResourceMemberRole::Mentioned->value]);
    ResourceRecommendation::query()->create(['resource_id' => $report->id, 'user_id' => $fatou->id, 'quote' => 'Un rapport précieux.']);
    ResourceRecommendation::query()->create(['resource_id' => $report->id, 'user_id' => $aminata->id, 'quote' => 'Je le recommande.']);
    $this->viewer->favoriteResources()->attach($report);

    libraryResource('Podcast lié', '2025-12-09', ['type' => ResourceType::Podcast], ['Femmes, paix et sécurité']);
    libraryResource('Sans rapport', '2026-05-01', [], ['Multilatéralisme']);

    $this->getJson($this->tenantUrl('/api/resources/women-mediators'))
        ->assertOk()
        ->assertJsonPath('data.title', 'Women Mediators')
        ->assertJsonPath('data.summary', 'Un rapport sur les femmes médiatrices.')
        ->assertJsonPath('data.pages', 42)
        ->assertJsonPath('data.languages', ['Français', 'Anglais'])
        ->assertJsonPath('data.region', 'Afrique de l’Ouest')
        ->assertJsonPath('data.organization', ['name' => 'Conciliation Resources', 'detail' => 'Organisation basée à Londres.', 'url' => 'https://www.c-r.org/'])
        ->assertJsonPath('data.is_favorite', true)
        ->assertJsonPath('data.mediators.*.name', ['Aminata Diallo', 'Fatou Ndiaye'])
        ->assertJsonPath('data.mediators.0.role', ['value' => 'contributor', 'label' => 'Contributrice'])
        ->assertJsonPath('data.mediators.0.note', 'Citée au chapitre 3.')
        ->assertJsonPath('data.mediators.0.quote', 'Je le recommande.')
        ->assertJsonPath('data.mediators.0.country', 'Sénégal')
        ->assertJsonPath('data.mediators.1.role', null)
        ->assertJsonPath('data.mediators.1.quote', 'Un rapport précieux.')
        ->assertJsonPath('data.event.title', 'Paris Peace Forum')
        ->assertJsonPath('data.event.place', 'Paris, France')
        ->assertJsonPath('data.event.note', 'Présenté au panel du forum.')
        ->assertJsonPath('data.event.attendee_count', 2)
        ->assertJsonPath('data.related.*.title', ['Podcast lié']);
});

it('puts the mediator who wrote a resource first, and gives no related resource without themes', function () {
    $mariam = libraryMember('Mariam', 'Keita');
    libraryResource('Analyse', '2025-09-15', ['author_id' => $mariam->id, 'source' => null, 'region' => null]);

    $this->getJson($this->tenantUrl('/api/resources/analyse'))
        ->assertOk()
        ->assertJsonPath('data.organization', null)
        ->assertJsonPath('data.region', 'Monde')
        ->assertJsonPath('data.mediators.0.name', 'Mariam Keita')
        ->assertJsonPath('data.mediators.0.role', ['value' => 'author', 'label' => 'Autrice'])
        ->assertJsonPath('data.event', null)
        ->assertJsonPath('data.related', []);

    $this->getJson($this->tenantUrl('/api/resources/inconnue'))->assertNotFound();
});
