<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Proxys de confiance
    |--------------------------------------------------------------------------
    |
    | Adresses ou plages (séparées par des virgules) des reverse proxies placés
    | devant l'application, lues par le middleware TrustProxies : sans elles,
    | toutes les requêtes semblent venir du proxy (limites de tentatives de
    | connexion communes à tout le monde) et le https n'est pas détecté.
    | Vide en dev ; en preprod, réseaux Docker de Traefik et de nginx.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
