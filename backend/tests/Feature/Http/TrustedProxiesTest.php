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
 * Requête telle qu'elle arrive de l'ingress K3s de la preprod : adresse du réseau
 * interne, IP de la visiteuse et protocole d'origine dans les en-têtes X-Forwarded-*.
 */
function requestThroughIngress(): TestResponse
{
    return test()
        ->withServerVariables(['REMOTE_ADDR' => '10.42.0.7'])
        ->withHeaders(['X-Forwarded-For' => '203.0.113.9', 'X-Forwarded-Proto' => 'https'])
        ->getJson('http://localhost/_test/client');
}

it('reads the visitor IP and https from a trusted proxy', function () {
    config(['trustedproxy.proxies' => '10.0.0.0/8,172.16.0.0/12']);

    requestThroughIngress()->assertOk()->assertExactJson(['ip' => '203.0.113.9', 'secure' => true]);
});

it('ignores the forwarded headers when no proxy is trusted', function () {
    config(['trustedproxy.proxies' => null]);

    requestThroughIngress()->assertOk()->assertExactJson(['ip' => '10.42.0.7', 'secure' => false]);
});
