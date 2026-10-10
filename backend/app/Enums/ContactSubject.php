<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Objet d'une prise de contact depuis la fiche d'une médiatrice (fenêtre « Contacter »).
 */
enum ContactSubject: string
{
    case Expertise = 'expertise';
    case EventInvitation = 'event_invitation';
    case CoMediation = 'co_mediation';
    case PeerExchange = 'peer_exchange';

    public function label(): string
    {
        return match ($this) {
            self::Expertise => 'Demande d’expertise',
            self::EventInvitation => 'Invitation à un événement',
            self::CoMediation => 'Proposition de co-médiation',
            self::PeerExchange => 'Échange entre pairs',
        };
    }
}
