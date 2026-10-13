<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Rôle d'une médiatrice à un événement (onglet « Médiatrices présentes » de la fiche).
 */
enum EventRole: string
{
    case Participant = 'participant';
    case Speaker = 'speaker';

    public function label(): string
    {
        return match ($this) {
            self::Participant => 'Participante',
            self::Speaker => 'Intervenante',
        };
    }
}
