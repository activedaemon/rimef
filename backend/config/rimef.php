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

    /*
    |--------------------------------------------------------------------------
    | Comptes de l'équipe
    |--------------------------------------------------------------------------
    |
    | Personnes réelles créées dans chaque tenant et dans tous les environnements
    | par TeamAccountsSeeder, avec leurs rôles (valeurs de App\Enums\Role). Sans mot
    | de passe défini, le compte en reçoit un aléatoire : le choisir via « Mot de
    | passe oublié ». Leur profil de médiatrice se remplit depuis l'application.
    |
    */

    'team' => [
        [
            'email' => env('RIMEF_OLIVIA_EMAIL', 'ocaeymaex@c-r.org'),
            'password' => env('RIMEF_OLIVIA_PASSWORD'),
            'first_name' => 'Olivia',
            'last_name' => 'Caeymaex',
            'roles' => ['admin', 'member'],
        ],
    ],

];
