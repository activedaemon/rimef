<?php

use App\Models\User;
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    config([
        'rimef.dev_admin.email' => 'admin@rimef.localhost',
        'rimef.dev_admin.password' => 'mot-de-passe-de-dev',
    ]);
});

it('creates the admin and member roles', function () {
    $this->seed(TenantDatabaseSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())->toBe(['admin', 'member']);
});

it('creates the development admin in the local environment only', function () {
    $this->seed(TenantDatabaseSeeder::class);
    expect(User::where('email', 'admin@rimef.localhost')->exists())->toBeFalse();

    app()->detectEnvironment(fn () => 'local');
    $this->seed(TenantDatabaseSeeder::class);

    expect(User::where('email', 'admin@rimef.localhost')->sole()->hasRole('admin'))->toBeTrue();
});

it('can run twice without duplicating the development admin', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->seed(TenantDatabaseSeeder::class);
    $this->seed(TenantDatabaseSeeder::class);

    expect(User::where('email', 'admin@rimef.localhost')->count())->toBe(1);
});
