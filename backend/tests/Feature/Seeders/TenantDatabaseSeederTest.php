<?php

use App\Models\Event;
use App\Models\Resource;
use App\Models\User;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    config([
        'rimef.superadmin.email' => 'david@active-daemon.com',
        'rimef.superadmin.password' => 'mot-de-passe-du-superadmin',
        'rimef.team' => [[
            'email' => 'OCaeymaex@c-r.org',
            'password' => 'mot-de-passe-d-olivia',
            'first_name' => 'Olivia',
            'last_name' => 'Caeymaex',
            'roles' => ['admin', 'member'],
        ]],
    ]);
});

it('creates the superadmin, admin and member roles', function () {
    $this->seed(TenantDatabaseSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())->toBe(['admin', 'member', 'superadmin']);
});

it('creates the superadmin David Gautier in every environment', function () {
    $this->seed(TenantDatabaseSeeder::class);

    $superAdmin = User::where('email', 'david@active-daemon.com')->sole();
    expect($superAdmin->name)->toBe('David Gautier')
        ->and($superAdmin->is_active)->toBeTrue()
        ->and($superAdmin->isSuperAdmin())->toBeTrue()
        ->and(Hash::check('mot-de-passe-du-superadmin', $superAdmin->password))->toBeTrue();
});

it('gives the superadmin a random password when none is configured', function () {
    config(['rimef.superadmin.password' => null]);

    $this->seed(TenantDatabaseSeeder::class);

    expect(User::where('email', 'david@active-daemon.com')->sole()->password)->not->toBeEmpty();
});

it('restores an existing account as superadmin without changing its password', function () {
    $existing = User::factory()->create([
        'email' => 'david@active-daemon.com',
        'first_name' => 'Admin',
        'last_name' => 'RIMeF',
        'password' => 'mot-de-passe-actuel',
    ]);
    $existing->assignRole('admin');

    $this->seed(TenantDatabaseSeeder::class);
    $this->seed(TenantDatabaseSeeder::class);

    $superAdmin = User::where('email', 'david@active-daemon.com')->sole();
    expect($superAdmin->name)->toBe('David Gautier')
        ->and($superAdmin->getRoleNames()->all())->toBe(['superadmin'])
        ->and(Hash::check('mot-de-passe-actuel', $superAdmin->password))->toBeTrue();
});

it('creates no superadmin when no email is configured', function () {
    config(['rimef.superadmin.email' => '', 'rimef.team' => []]);

    $this->seed(TenantDatabaseSeeder::class);

    expect(User::count())->toBe(0);
});

it('creates the team accounts with their roles in every environment', function () {
    $this->seed(TenantDatabaseSeeder::class);

    $olivia = User::where('email', 'ocaeymaex@c-r.org')->sole();
    expect($olivia->name)->toBe('Olivia Caeymaex')
        ->and($olivia->is_active)->toBeTrue()
        ->and($olivia->email_verified_at)->not->toBeNull()
        ->and($olivia->getRoleNames()->sort()->values()->all())->toBe(['admin', 'member'])
        ->and(Hash::check('mot-de-passe-d-olivia', $olivia->password))->toBeTrue();
});

it('restores a team account without changing its password', function () {
    $existing = User::factory()->create([
        'email' => 'ocaeymaex@c-r.org',
        'first_name' => 'O.',
        'last_name' => 'C.',
        'password' => 'mot-de-passe-choisi',
        'is_active' => false,
    ]);

    $this->seed(TenantDatabaseSeeder::class);
    $this->seed(TenantDatabaseSeeder::class);

    $olivia = User::where('email', 'ocaeymaex@c-r.org')->sole();
    expect($olivia->id)->toBe($existing->id)
        ->and($olivia->name)->toBe('Olivia Caeymaex')
        ->and($olivia->is_active)->toBeTrue()
        ->and($olivia->getRoleNames()->sort()->values()->all())->toBe(['admin', 'member'])
        ->and(Hash::check('mot-de-passe-choisi', $olivia->password))->toBeTrue();
});

it('creates no demo data outside the local environment by default', function () {
    $this->seed(TenantDatabaseSeeder::class);

    expect(User::where('email', 'like', '%@example.org')->exists())->toBeFalse();
});

it('creates the demo data outside the local environment when enabled', function () {
    Storage::fake('local');
    config(['rimef.demo_data' => true]);

    $this->seed(TenantDatabaseSeeder::class);

    expect(User::where('email', 'like', '%@example.org')->exists())->toBeTrue()
        ->and(Event::exists())->toBeTrue()
        ->and(Resource::exists())->toBeTrue();
});

it('gives a team account a random password when none is configured', function () {
    config(['rimef.team.0.password' => null]);

    $this->seed(TenantDatabaseSeeder::class);

    expect(User::where('email', 'ocaeymaex@c-r.org')->sole()->password)->not->toBeEmpty();
});
