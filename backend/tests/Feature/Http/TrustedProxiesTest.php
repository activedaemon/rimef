<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Route::get('/_test/client', fn (Request $request): array => [
        'ip' => $request->ip(),
        'secure' => $request->isSecure(),
    ]);
});

/**
 * Requête telle qu'elle arrive du reverse proxy de la preprod : adresse d'un réseau
 * interne, IP de la visiteuse et protocole d'origine dans les en-têtes X-Forwarded-*.
 */
function requestThroughProxy(): TestResponse
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.9', 'X-Forwarded-Proto' => 'https'])
        ->getJson('http://localhost/_test/client');
}

it('reads the visitor IP and https from a trusted proxy', function () {
    config(['trustedproxy.proxies' => '172.16.0.0/12']);

    requestThroughProxy()->assertOk()->assertExactJson(['ip' => '203.0.113.9', 'secure' => true]);
});

it('ignores the forwarded headers when no proxy is trusted', function () {
    config(['trustedproxy.proxies' => null]);

    requestThroughProxy()->assertOk()->assertExactJson(['ip' => '172.18.0.5', 'secure' => false]);
});
