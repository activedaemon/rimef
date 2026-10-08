<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Données initiales de chaque base tenant, à la création du tenant (TenancyServiceProvider)
 * et à chaque `tenants:seed`. Relançable sans risque.
 */
class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
