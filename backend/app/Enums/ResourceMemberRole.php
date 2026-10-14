<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lien d'une médiatrice du réseau avec une ressource (fiche ressource, badge de sa carte).
 */
enum ResourceMemberRole: string
{
    case Contributor = 'contributor';
    case Mentioned = 'mentioned';

    public function label(): string
    {
        return match ($this) {
            self::Contributor => 'Contributrice',
            self::Mentioned => 'Citée',
        };
    }
}
