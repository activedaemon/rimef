<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Région du monde, déduite du pays (config/directory.php) : filtre « Région » de l'annuaire.
 */
enum Region: string
{
    case WestAfrica = 'west_africa';
    case NorthAfrica = 'north_africa';
    case CentralAfrica = 'central_africa';
    case GreatLakes = 'great_lakes';
    case EastAfrica = 'east_africa';
    case IndianOcean = 'indian_ocean';
    case Europe = 'europe';
    case NorthAmerica = 'north_america';
    case LatinAmerica = 'latin_america';
    case MiddleEast = 'middle_east';
    case AsiaPacific = 'asia_pacific';

    public function label(): string
    {
        return match ($this) {
            self::WestAfrica => 'Afrique de l’Ouest',
            self::NorthAfrica => 'Afrique du Nord',
            self::CentralAfrica => 'Afrique centrale',
            self::GreatLakes => 'Afrique des Grands Lacs',
            self::EastAfrica => 'Afrique de l’Est',
            self::IndianOcean => 'Océan Indien',
            self::Europe => 'Europe',
            self::NorthAmerica => 'Amérique du Nord',
            self::LatinAmerica => 'Amérique latine et Caraïbes',
            self::MiddleEast => 'Moyen-Orient',
            self::AsiaPacific => 'Asie-Pacifique',
        };
    }
}
