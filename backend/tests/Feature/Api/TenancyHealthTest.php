<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Tenant de test : sa base (SQLite) est créée et migrée à la création.
    $this->tenant = Tenant::create(['id' => 'test', 'name' => 'Tenant de test']);
    $this->tenant->domains()->create(['domain' => 'test.rimef.localhost']);
});

afterEach(function () {
    tenancy()->end();
    // Supprime aussi la base du tenant (fichier SQLite temporaire).
    $this->tenant->delete();
});

test('health endpoint answers in the central context on a central domain', function () {
    $this->getJson('http://supervisor.rimef.localhost/api/health')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'context' => 'central',
            'tenant' => null,
        ]);
});

test('health endpoint answers with the tenant database on a tenant domain', function () {
    $response = $this->getJson('http://test.rimef.localhost/api/health')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'context' => 'tenant',
            'tenant' => 'test',
            'database' => ['status' => 'ok'],
        ]);

    // SQLite (tests) renvoie le chemin du fichier, MySQL le nom de la base.
    expect(basename($response->json('database.name')))->toBe('rimef_tenant_test');
});

test('an unknown domain is not found', function () {
    $this->getJson('http://inconnu.rimef.localhost/api/health')
        ->assertNotFound();
});
