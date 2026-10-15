<?php

use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\Expertise;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    Storage::fake('local');

    $this->mediator = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo']);
    $this->mediator->assignRole(Role::Member);
    $this->profile = MemberProfile::factory()->for($this->mediator)->create([
        'country_code' => 'SN',
        'organization_type' => OrganizationType::CivilSociety,
    ]);
    Expertise::firstOrCreate(['name' => 'Médiation communautaire']);
    Expertise::firstOrCreate(['name' => 'Gouvernance locale']);
});

/**
 * @return array<string, mixed>
 */
function profilePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'first_name' => 'Aminata',
        'last_name' => 'Sow',
        'job_title' => 'Conseillère en paix et sécurité',
        'organization_type' => 'civil_society',
        'organization_name' => 'Plateforme des femmes pour la paix en Casamance',
        'country_code' => 'SN',
        'city' => 'Dakar',
        'tagline' => 'Favoriser le dialogue inclusif.',
        'bio' => 'Médiatrice en Afrique de l’Ouest.',
        'experience_since' => 2011,
        'audiences' => 'Femmes, jeunes',
        'is_available' => true,
        'expertises' => ['Gouvernance locale', 'Médiation communautaire'],
        'zones' => ['ML', 'SN'],
        'links' => [
            'linkedin' => 'https://www.linkedin.com/in/aminata-diallo',
            'website' => '',
            'organization' => 'https://www.femmes-paix-casamance.org',
            'other' => ['label' => 'Podcast', 'url' => 'https://podcast.example.org'],
        ],
    ], $overrides);
}

it('requires an open session', function () {
    $this->getJson($this->tenantUrl('/api/profile'))->assertUnauthorized();
    $this->putJson($this->tenantUrl('/api/profile'), profilePayload())->assertUnauthorized();
});

it('gives the current values and the choices of the form', function () {
    $this->actingAs($this->mediator)
        ->getJson($this->tenantUrl('/api/profile'))
        ->assertOk()
        ->assertJsonPath('data.has_sheet', true)
        ->assertJsonPath('data.first_name', 'Aminata')
        ->assertJsonPath('data.country_code', 'SN')
        ->assertJsonPath('data.organization_type', 'civil_society')
        ->assertJsonPath('data.links.other', ['label' => null, 'url' => null])
        ->assertJsonPath('meta.options.organization_types.0', ['value' => 'civil_society', 'label' => 'Société civile'])
        ->assertJsonFragment(['code' => 'SN', 'name' => 'Sénégal'])
        ->assertJsonFragment(['name' => 'Gouvernance locale', 'count' => 0]);
});

it('saves the profile, keeps the order of expertises and zones, and drops empty links', function () {
    $this->actingAs($this->mediator)
        ->putJson($this->tenantUrl('/api/profile'), profilePayload())
        ->assertOk()
        ->assertJsonPath('data.last_name', 'Sow')
        ->assertJsonPath('data.expertises', ['Gouvernance locale', 'Médiation communautaire'])
        ->assertJsonPath('data.zones', ['ML', 'SN'])
        ->assertJsonPath('data.links.website', null);

    $profile = $this->profile->fresh();
    expect($profile->organization_name)->toBe('Plateforme des femmes pour la paix en Casamance')
        ->and($profile->is_available)->toBeTrue()
        ->and($profile->links)->toBe([
            'linkedin' => 'https://www.linkedin.com/in/aminata-diallo',
            'organization' => 'https://www.femmes-paix-casamance.org',
            'other' => ['label' => 'Podcast', 'url' => 'https://podcast.example.org'],
        ]);
});

it('keeps the address of the profile when the name changes', function () {
    $this->actingAs($this->mediator)->putJson($this->tenantUrl('/api/profile'), profilePayload());

    expect($this->mediator->fresh()->slug)->toBe('aminata-diallo');
    $this->getJson($this->tenantUrl('/api/members/aminata-diallo'))
        ->assertOk()
        ->assertJsonPath('data.name', 'Aminata Sow');
});

it('shows the organization name and the links on the profile', function () {
    $this->actingAs($this->mediator)->putJson($this->tenantUrl('/api/profile'), profilePayload());

    $this->getJson($this->tenantUrl('/api/members/aminata-diallo'))
        ->assertJsonPath('data.organization', 'Plateforme des femmes pour la paix en Casamance')
        ->assertJsonPath('data.links', [
            ['type' => 'linkedin', 'label' => 'LinkedIn', 'url' => 'https://www.linkedin.com/in/aminata-diallo'],
            ['type' => 'organization', 'label' => 'Organisation', 'url' => 'https://www.femmes-paix-casamance.org'],
            ['type' => 'other', 'label' => 'Podcast', 'url' => 'https://podcast.example.org'],
        ]);
});

it('rejects unknown expertises and countries, and links that are not https', function () {
    $this->actingAs($this->mediator)
        ->putJson($this->tenantUrl('/api/profile'), profilePayload([
            'expertises' => ['Astrologie'],
            'zones' => ['XX'],
            'country_code' => 'XX',
            'links' => [
                'linkedin' => 'https://example.org/aminata',
                'website' => 'http://aminata.example.org',
                'other' => ['label' => 'Podcast', 'url' => ''],
            ],
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'expertises.0', 'zones.0', 'country_code', 'links.linkedin', 'links.website', 'links.other.url',
        ]);
});

it('limits an administration account without sheet to its first and last name', function () {
    $admin = User::factory()->create(['first_name' => 'David', 'last_name' => 'Gautier']);
    $admin->assignRole(Role::Admin);

    $this->actingAs($admin)
        ->putJson($this->tenantUrl('/api/profile'), profilePayload(['first_name' => 'Dav']))
        ->assertOk()
        ->assertJsonPath('data.has_sheet', false)
        ->assertJsonPath('data.first_name', 'Dav');

    expect($admin->memberProfile()->exists())->toBeFalse();

    $this->postJson($this->tenantUrl('/api/profile/photo'), [
        'photo' => UploadedFile::fake()->image('portrait.jpg', 600, 600),
    ])->assertForbidden();
});

it('converts the photo into a 400 px square webp, and removes it', function () {
    $this->actingAs($this->mediator)
        ->postJson($this->tenantUrl('/api/profile/photo'), [
            'photo' => UploadedFile::fake()->image('portrait.jpg', 800, 600),
        ])
        ->assertOk()
        ->assertJsonPath('data.photo_url', fn (?string $url): bool => str_starts_with((string) $url, "/api/members/{$this->mediator->id}/photo?v="));

    $path = "members/{$this->profile->id}.webp";
    Storage::disk('local')->assertExists($path);
    expect(getimagesizefromstring(Storage::disk('local')->get($path)))
        ->toMatchArray([0 => 400, 1 => 400, 'mime' => 'image/webp']);

    $this->deleteJson($this->tenantUrl('/api/profile/photo'))
        ->assertOk()
        ->assertJsonPath('data.photo_url', null);
    Storage::disk('local')->assertMissing($path);
});

it('rejects photos that are too small, too big or not images', function (UploadedFile $photo) {
    $this->actingAs($this->mediator)
        ->postJson($this->tenantUrl('/api/profile/photo'), ['photo' => $photo])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('photo');
})->with([
    'too small' => fn () => UploadedFile::fake()->image('portrait.jpg', 300, 300),
    'too big' => fn () => UploadedFile::fake()->image('portrait.jpg', 600, 600)->size(6 * 1024),
    'not an image' => fn () => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
]);
