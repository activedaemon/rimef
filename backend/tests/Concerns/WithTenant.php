<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Tenant;
use Database\Seeders\RoleSeeder;

/**
 * Tenant de test `test` sur test.rimef.localhost, avec sa base SQLite temporaire
 * migrée et ses rôles. La tenancy reste initialisée pour créer les données du test.
 *
 * Préparé par Laravel (setUpWithTenant) ; supprimé via TestCase::tearDown(), avant
 * l'annulation de la transaction de test (sinon elle viserait la base du tenant).
 */
trait WithTenant
{
    protected const TENANT_HOST = 'test.rimef.localhost';

    protected Tenant $tenant;

    protected function setUpWithTenant(): void
    {
        $this->tenant = Tenant::create(['id' => 'test', 'name' => 'Tenant de test']);
        $this->tenant->domains()->create(['domain' => self::TENANT_HOST]);

        tenancy()->initialize($this->tenant);
        app(RoleSeeder::class)->run();
    }

    protected function removeTestTenant(): void
    {
        tenancy()->end();
        $this->tenant->delete();
    }

    /**
     * URL absolue sur le domaine du tenant de test.
     */
    protected function tenantUrl(string $path): string
    {
        return 'http://'.self::TENANT_HOST.$path;
    }
}
