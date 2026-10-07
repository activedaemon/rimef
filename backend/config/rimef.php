<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domaine du tenant principal
    |--------------------------------------------------------------------------
    |
    | Domaine complet qui sert le tenant `rimef` (rimef.localhost en dev,
    | rimef.org en production, à confirmer). Utilisé par TenantSeeder.
    |
    */

    'tenant_domain' => env('RIMEF_TENANT_DOMAIN', 'rimef.localhost'),

    /*
    |--------------------------------------------------------------------------
    | Compte administratrice de développement
    |--------------------------------------------------------------------------
    |
    | Créé par TenantDatabaseSeeder dans chaque tenant, en environnement local
    | uniquement et seulement si email et mot de passe sont renseignés.
    |
    */

    'dev_admin' => [
        'email' => env('RIMEF_DEV_ADMIN_EMAIL'),
        'password' => env('RIMEF_DEV_ADMIN_PASSWORD'),
    ],

];
