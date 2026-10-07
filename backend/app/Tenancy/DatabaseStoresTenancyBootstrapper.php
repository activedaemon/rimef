<?php

declare(strict_types=1);

namespace App\Tenancy;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Session\Store;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

/**
 * Réaligne le cache et les sessions en base sur la connexion du tenant (puis centrale).
 *
 * Ces stores figent leur connexion à leur création. Or Laravel peut les créer avant
 * l'identification du tenant : le limiteur de Fortify (RateLimiter::for au démarrage),
 * le cache de spatie/laravel-permission, ou la session via le contrôleur de connexion
 * de Fortify (instancié pendant la collecte des middlewares de la route). Sans ce
 * bootstrapper, sessions, tentatives de connexion et rôles en cache partaient dans la
 * base centrale et seraient partagés entre tenants.
 *
 * À placer après DatabaseTenancyBootstrapper (config/tenancy.php) : stancl exécute
 * bootstrap() et revert() dans l'ordre de la liste.
 */
class DatabaseStoresTenancyBootstrapper implements TenancyBootstrapper
{
    public function __construct(private readonly Application $app) {}

    public function bootstrap(Tenant $tenant): void
    {
        $this->useCurrentConnection();
    }

    public function revert(): void
    {
        $this->useCurrentConnection();
    }

    private function useCurrentConnection(): void
    {
        $database = $this->app->make('db');

        /** @var array<string, array{driver: string, connection?: string|null, lock_connection?: string|null}> $stores */
        $stores = config('cache.stores');

        foreach ($stores as $name => $config) {
            if ($config['driver'] !== 'database') {
                continue;
            }

            $store = $this->app->make('cache')->store($name)->getStore();
            $store->setConnection($database->connection($config['connection'] ?? null));
            $store->setLockConnection($database->connection($config['lock_connection'] ?? $config['connection'] ?? null));
        }

        /** @var Store $session */
        foreach ($this->app->make('session')->getDrivers() as $session) {
            if ($session->getHandler() instanceof DatabaseSessionHandler) {
                $session->setHandler(new DatabaseSessionHandler(
                    $database->connection(config('session.connection')),
                    config('session.table'),
                    (int) config('session.lifetime'),
                    $this->app,
                ));
            }
        }
    }
}
