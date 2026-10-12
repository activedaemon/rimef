<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Type d'un événement de l'Agenda (filtre « Type »).
 */
enum EventType: string
{
    case Workshop = 'workshop';
    case Conference = 'conference';
    case Training = 'training';
    case InternationalForum = 'international_forum';
    case Summit = 'summit';

    public function label(): string
    {
        return match ($this) {
            self::Workshop => 'Atelier',
            self::Conference => 'Conférence',
            self::Training => 'Formation',
            self::InternationalForum => 'Forum international',
            self::Summit => 'Sommet',
        };
    }
}
