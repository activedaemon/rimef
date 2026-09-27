<?php

use Illuminate\Support\Facades\DB;

test('health endpoint reports the application and database as up', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'app' => config('app.name'),
            'database' => ['status' => 'ok'],
        ]);
});

test('health endpoint reports a degraded state when the database is unreachable', function () {
    DB::shouldReceive('connection->getPdo')->andThrow(new RuntimeException('Connection refused'));
    DB::shouldReceive('connection->getDatabaseName')->andReturn('rimef_central');

    $this->getJson('/api/health')
        ->assertServiceUnavailable()
        ->assertJson([
            'status' => 'degraded',
            'database' => ['status' => 'down'],
        ]);
});
