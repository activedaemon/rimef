<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

it('builds the slug from the first and last name, without accents', function () {
    $user = User::factory()->create(['first_name' => 'Leïla', 'last_name' => 'El Amrani']);

    expect($user->slug)->toBe('leila-el-amrani');
});

it('gives namesakes the first free suffix', function () {
    $first = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo']);
    $second = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo']);
    $third = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo']);

    expect([$first->slug, $second->slug, $third->slug])
        ->toBe(['aminata-diallo', 'aminata-diallo-2', 'aminata-diallo-3']);
});

it('updates the slug when the name changes, and keeps it otherwise', function () {
    $user = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo']);

    $user->update(['email' => 'aminata@example.org']);
    expect($user->slug)->toBe('aminata-diallo');

    $user->update(['last_name' => 'Sow']);
    expect($user->slug)->toBe('aminata-sow');
});

it('keeps its own slug when the name only changes its accents', function () {
    User::factory()->create(['first_name' => 'Leila', 'last_name' => 'Bouzid']);
    $user = User::factory()->create(['first_name' => 'Leila', 'last_name' => 'Bouzid']);

    $user->update(['first_name' => 'Leïla']);

    expect($user->slug)->toBe('leila-bouzid-2');
});
