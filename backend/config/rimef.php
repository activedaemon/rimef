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
    | Superadmin
    |--------------------------------------------------------------------------
    |
    | Compte protégé (ni supprimable, ni désactivable), créé dans chaque tenant et
    | dans tous les environnements par SuperAdminSeeder. Sans mot de passe défini,
    | il en reçoit un aléatoire : le choisir via « Mot de passe oublié ».
    |
    */

    'superadmin' => [
        'email' => env('RIMEF_SUPERADMIN_EMAIL', 'david@active-daemon.com'),
        'password' => env('RIMEF_SUPERADMIN_PASSWORD'),
        'first_name' => 'David',
        'last_name' => 'Gautier',
    ],

];
