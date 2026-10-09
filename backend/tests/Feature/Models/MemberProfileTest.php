<?php

use App\Models\MemberProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

it('replaces the languages of a mediator, in the given order', function () {
    $profile = MemberProfile::factory()->speaking('fr', 'en')->create();

    $profile->syncLanguages(['wo', 'fr']);

    expect($profile->languages->pluck('language_code')->all())->toBe(['wo', 'fr'])
        ->and($profile->languages->pluck('position')->all())->toBe([0, 1]);
});

it('removes every language when given none', function () {
    $profile = MemberProfile::factory()->speaking('fr')->create();

    $profile->syncLanguages([]);

    expect($profile->languages)->toBeEmpty();
});
