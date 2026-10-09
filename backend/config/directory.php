<?php

use App\Enums\Region;

/*
|--------------------------------------------------------------------------
| Annuaire du réseau : pays
|--------------------------------------------------------------------------
|
| Les profils stockent le code ISO 3166-1 alpha-2 du pays ; libellé et région
| sont définis ici. Ajouter un pays = une ligne.
|
*/

return [

    'countries' => [
        // Afrique de l’Ouest
        'BJ' => ['name' => 'Bénin', 'region' => Region::WestAfrica->value],
        'BF' => ['name' => 'Burkina Faso', 'region' => Region::WestAfrica->value],
        'CV' => ['name' => 'Cap-Vert', 'region' => Region::WestAfrica->value],
        'CI' => ['name' => 'Côte d’Ivoire', 'region' => Region::WestAfrica->value],
        'GM' => ['name' => 'Gambie', 'region' => Region::WestAfrica->value],
        'GH' => ['name' => 'Ghana', 'region' => Region::WestAfrica->value],
        'GN' => ['name' => 'Guinée', 'region' => Region::WestAfrica->value],
        'GW' => ['name' => 'Guinée-Bissau', 'region' => Region::WestAfrica->value],
        'LR' => ['name' => 'Libéria', 'region' => Region::WestAfrica->value],
        'ML' => ['name' => 'Mali', 'region' => Region::WestAfrica->value],
        'MR' => ['name' => 'Mauritanie', 'region' => Region::WestAfrica->value],
        'NE' => ['name' => 'Niger', 'region' => Region::WestAfrica->value],
        'NG' => ['name' => 'Nigéria', 'region' => Region::WestAfrica->value],
        'SN' => ['name' => 'Sénégal', 'region' => Region::WestAfrica->value],
        'SL' => ['name' => 'Sierra Leone', 'region' => Region::WestAfrica->value],
        'TG' => ['name' => 'Togo', 'region' => Region::WestAfrica->value],
        // Afrique du Nord
        'DZ' => ['name' => 'Algérie', 'region' => Region::NorthAfrica->value],
        'EG' => ['name' => 'Égypte', 'region' => Region::NorthAfrica->value],
        'LY' => ['name' => 'Libye', 'region' => Region::NorthAfrica->value],
        'MA' => ['name' => 'Maroc', 'region' => Region::NorthAfrica->value],
        'TN' => ['name' => 'Tunisie', 'region' => Region::NorthAfrica->value],
        // Afrique centrale
        'CM' => ['name' => 'Cameroun', 'region' => Region::CentralAfrica->value],
        'CF' => ['name' => 'République centrafricaine', 'region' => Region::CentralAfrica->value],
        'TD' => ['name' => 'Tchad', 'region' => Region::CentralAfrica->value],
        'CG' => ['name' => 'Congo', 'region' => Region::CentralAfrica->value],
        'CD' => ['name' => 'République démocratique du Congo', 'region' => Region::CentralAfrica->value],
        'GA' => ['name' => 'Gabon', 'region' => Region::CentralAfrica->value],
        'GQ' => ['name' => 'Guinée équatoriale', 'region' => Region::CentralAfrica->value],
        'ST' => ['name' => 'Sao Tomé-et-Principe', 'region' => Region::CentralAfrica->value],
        // Afrique des Grands Lacs
        'BI' => ['name' => 'Burundi', 'region' => Region::GreatLakes->value],
        'RW' => ['name' => 'Rwanda', 'region' => Region::GreatLakes->value],
        'UG' => ['name' => 'Ouganda', 'region' => Region::GreatLakes->value],
        // Afrique de l’Est
        'DJ' => ['name' => 'Djibouti', 'region' => Region::EastAfrica->value],
        'ET' => ['name' => 'Éthiopie', 'region' => Region::EastAfrica->value],
        'KE' => ['name' => 'Kenya', 'region' => Region::EastAfrica->value],
        'SO' => ['name' => 'Somalie', 'region' => Region::EastAfrica->value],
        'SD' => ['name' => 'Soudan', 'region' => Region::EastAfrica->value],
        'SS' => ['name' => 'Soudan du Sud', 'region' => Region::EastAfrica->value],
        'TZ' => ['name' => 'Tanzanie', 'region' => Region::EastAfrica->value],
        // Océan Indien
        'KM' => ['name' => 'Comores', 'region' => Region::IndianOcean->value],
        'MG' => ['name' => 'Madagascar', 'region' => Region::IndianOcean->value],
        'MU' => ['name' => 'Maurice', 'region' => Region::IndianOcean->value],
        'SC' => ['name' => 'Seychelles', 'region' => Region::IndianOcean->value],
        // Europe
        'DE' => ['name' => 'Allemagne', 'region' => Region::Europe->value],
        'AL' => ['name' => 'Albanie', 'region' => Region::Europe->value],
        'AM' => ['name' => 'Arménie', 'region' => Region::Europe->value],
        'AT' => ['name' => 'Autriche', 'region' => Region::Europe->value],
        'BE' => ['name' => 'Belgique', 'region' => Region::Europe->value],
        'BG' => ['name' => 'Bulgarie', 'region' => Region::Europe->value],
        'ES' => ['name' => 'Espagne', 'region' => Region::Europe->value],
        'FR' => ['name' => 'France', 'region' => Region::Europe->value],
        'GR' => ['name' => 'Grèce', 'region' => Region::Europe->value],
        'IT' => ['name' => 'Italie', 'region' => Region::Europe->value],
        'LU' => ['name' => 'Luxembourg', 'region' => Region::Europe->value],
        'MD' => ['name' => 'Moldavie', 'region' => Region::Europe->value],
        'MC' => ['name' => 'Monaco', 'region' => Region::Europe->value],
        'NO' => ['name' => 'Norvège', 'region' => Region::Europe->value],
        'NL' => ['name' => 'Pays-Bas', 'region' => Region::Europe->value],
        'PT' => ['name' => 'Portugal', 'region' => Region::Europe->value],
        'RO' => ['name' => 'Roumanie', 'region' => Region::Europe->value],
        'GB' => ['name' => 'Royaume-Uni', 'region' => Region::Europe->value],
        'SE' => ['name' => 'Suède', 'region' => Region::Europe->value],
        'CH' => ['name' => 'Suisse', 'region' => Region::Europe->value],
        // Amérique du Nord
        'CA' => ['name' => 'Canada', 'region' => Region::NorthAmerica->value],
        'US' => ['name' => 'États-Unis', 'region' => Region::NorthAmerica->value],
        // Amérique latine et Caraïbes
        'BR' => ['name' => 'Brésil', 'region' => Region::LatinAmerica->value],
        'CO' => ['name' => 'Colombie', 'region' => Region::LatinAmerica->value],
        'HT' => ['name' => 'Haïti', 'region' => Region::LatinAmerica->value],
        'MX' => ['name' => 'Mexique', 'region' => Region::LatinAmerica->value],
        'DO' => ['name' => 'République dominicaine', 'region' => Region::LatinAmerica->value],
        // Moyen-Orient
        'AE' => ['name' => 'Émirats arabes unis', 'region' => Region::MiddleEast->value],
        'IQ' => ['name' => 'Irak', 'region' => Region::MiddleEast->value],
        'JO' => ['name' => 'Jordanie', 'region' => Region::MiddleEast->value],
        'LB' => ['name' => 'Liban', 'region' => Region::MiddleEast->value],
        'PS' => ['name' => 'Palestine', 'region' => Region::MiddleEast->value],
        'QA' => ['name' => 'Qatar', 'region' => Region::MiddleEast->value],
        'SY' => ['name' => 'Syrie', 'region' => Region::MiddleEast->value],
        'YE' => ['name' => 'Yémen', 'region' => Region::MiddleEast->value],
        // Asie-Pacifique
        'KH' => ['name' => 'Cambodge', 'region' => Region::AsiaPacific->value],
        'JP' => ['name' => 'Japon', 'region' => Region::AsiaPacific->value],
        'LA' => ['name' => 'Laos', 'region' => Region::AsiaPacific->value],
        'PH' => ['name' => 'Philippines', 'region' => Region::AsiaPacific->value],
        'VU' => ['name' => 'Vanuatu', 'region' => Region::AsiaPacific->value],
        'VN' => ['name' => 'Viêt Nam', 'region' => Region::AsiaPacific->value],
    ],

];
