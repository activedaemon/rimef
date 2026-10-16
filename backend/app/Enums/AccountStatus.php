<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * État d'un compte dans la rubrique Administration.
 *
 * Une invitation non acceptée reste « en attente » pendant la validité du lien
 * (config auth.passwords.invitations.expire), puis devient « expirée ».
 */
enum AccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actifs',
            self::Inactive => 'Désactivés',
            self::Pending => 'Invitations en attente',
            self::Expired => 'Invitations expirées',
        };
    }
}
