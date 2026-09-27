<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Données initiales de la base centrale.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TenantSeeder::class);
    }
}
