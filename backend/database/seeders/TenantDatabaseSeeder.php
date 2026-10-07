<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données initiales de chaque base tenant (lancé par `tenants:seed`, idempotent).
 */
class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        if (app()->isLocal()) {
            $this->seedDevAdmin();
        }
    }

    /**
     * Administratrice de développement, définie dans backend/.env (RIMEF_DEV_ADMIN_*).
     */
    private function seedDevAdmin(): void
    {
        $email = config('rimef.dev_admin.email');
        $password = config('rimef.dev_admin.password');

        if (blank($email) || blank($password)) {
            return;
        }

        $admin = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'first_name' => 'Admin',
                'last_name' => 'RIMEF',
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles([Role::Admin->value]);
    }
}
