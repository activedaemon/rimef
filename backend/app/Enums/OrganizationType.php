<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Type d'organisation d'une médiatrice (filtre « Organisation » de l'annuaire).
 */
enum OrganizationType: string
{
    case CivilSociety = 'civil_society';
    case InternationalOrganization = 'international_organization';
    case LocalAuthority = 'local_authority';
    case Diplomacy = 'diplomacy';
    case Research = 'research';

    public function label(): string
    {
        return match ($this) {
            self::CivilSociety => 'Société civile',
            self::InternationalOrganization => 'Organisation internationale',
            self::LocalAuthority => 'Collectivité territoriale',
            self::Diplomacy => 'Diplomatie',
            self::Research => 'Recherche et université',
        };
    }
}
