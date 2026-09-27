<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

/**
 * Crée les tenants de la plateforme (base centrale).
 *
 * La création d'un tenant déclenche la création de sa base `rimef_tenant_<id>`
 * et l'exécution des migrations tenant (voir TenancyServiceProvider).
 * Idempotent : peut être relancé sans dupliquer les tenants ni les domaines.
 */
class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'id' => 'rimef',
                'name' => 'RIMeF',
                'domains' => [config('rimef.tenant_domain')],
            ],
        ];

        foreach ($tenants as $data) {
            $tenant = Tenant::find($data['id'])
                ?? Tenant::create(['id' => $data['id'], 'name' => $data['name']]);

            foreach ($data['domains'] as $domain) {
                $tenant->domains()->firstOrCreate(['domain' => $domain]);
            }
        }
    }
}
