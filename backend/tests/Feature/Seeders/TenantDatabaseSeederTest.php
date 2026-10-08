<?php

use App\Models\User;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    config([
        'rimef.superadmin.email' => 'david@active-daemon.com',
        'rimef.superadmin.password' => 'mot-de-passe-du-superadmin',
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
    config(['rimef.superadmin.email' => '']);

    $this->seed(TenantDatabaseSeeder::class);

    expect(User::count())->toBe(0);
});
