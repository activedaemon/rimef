<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    // Les stores sont créés avant l'identification du tenant, comme en production
    tenancy()->end();
});

it('points a cache store created before the tenant to the tenant database, then back', function () {
    $store = Cache::store('database')->getStore();
    $centralConnection = $store->getConnection()->getName();

    tenancy()->initialize($this->tenant);
    expect($store->getConnection()->getName())->toBe('tenant');

    tenancy()->end();
    expect($store->getConnection()->getName())->toBe($centralConnection);
});

it('writes a session created before the tenant into the tenant database', function () {
    config(['session.driver' => 'database']);
    app('session')->forgetDrivers();
    $session = app('session')->driver();

    tenancy()->initialize($this->tenant);
    $session->put('membre', 'aminata');
    $session->save();

    $this->assertDatabaseCount('sessions', 1);
});
