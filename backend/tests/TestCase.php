<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // Le tenant doit être fermé avant que RefreshDatabase annule sa transaction
        // sur la connexion par défaut (sinon la connexion du tenant est visée).
        $this->removeTestTenant();

        parent::tearDown();
    }

    /**
     * Supprime le tenant de test : sans effet ici, redéfinie par le trait WithTenant.
     */
    protected function removeTestTenant(): void {}
}
