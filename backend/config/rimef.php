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

];
