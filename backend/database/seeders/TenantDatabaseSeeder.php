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
            TeamAccountsSeeder::class,
            ExpertiseSeeder::class,
        ]);

        // Médiatrices fictives et événements de démonstration : poste de développement et recette uniquement.
        if (app()->environment('local') || config('rimef.demo_data')) {
            $this->call([DemoMembersSeeder::class, DemoEventsSeeder::class, DemoResourcesSeeder::class]);
        }
    }
}
