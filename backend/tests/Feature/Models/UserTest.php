<?php

use App\Enums\Role;
use App\Exceptions\ProtectedSuperAdminException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole(Role::SuperAdmin->value);
});

it('refuses to delete the superadmin', function () {
    expect(fn () => $this->superAdmin->delete())
        ->toThrow(ProtectedSuperAdminException::class, 'ne peut pas être supprimé');

    expect(User::whereKey($this->superAdmin->id)->exists())->toBeTrue();
});

it('refuses to deactivate the superadmin', function () {
    expect(fn () => $this->superAdmin->update(['is_active' => false]))
        ->toThrow(ProtectedSuperAdminException::class, 'ne peut pas être désactivé');

    expect($this->superAdmin->fresh()->is_active)->toBeTrue();
});

it('refuses to remove the superadmin role', function () {
    expect(fn () => $this->superAdmin->removeRole(Role::SuperAdmin->value))
        ->toThrow(ProtectedSuperAdminException::class, 'ne peut pas perdre son rôle');

    expect(fn () => $this->superAdmin->syncRoles([Role::Admin->value]))
        ->toThrow(ProtectedSuperAdminException::class);

    expect($this->superAdmin->fresh()->isSuperAdmin())->toBeTrue();
});

it('still lets the superadmin get an additional role', function () {
    $this->superAdmin->syncRoles([Role::SuperAdmin->value, Role::Admin->value]);

    expect($this->superAdmin->fresh()->getRoleNames()->sort()->values()->all())
        ->toBe(['admin', 'superadmin']);
});

it('lets an ordinary admin be deactivated and deleted', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::Admin->value);

    $admin->update(['is_active' => false]);
    $admin->delete();

    expect(User::whereKey($admin->id)->exists())->toBeFalse();
});

it('grants every ability to the superadmin only', function () {
    $member = User::factory()->create();
    $member->assignRole(Role::Member->value);

    expect(Gate::forUser($this->superAdmin)->allows('ability-without-definition'))->toBeTrue()
        ->and(Gate::forUser($member)->allows('ability-without-definition'))->toBeFalse();
});
