<?php

declare(strict_types=1);

namespace App\Exceptions;

use LogicException;

/**
 * Tentative de supprimer, désactiver ou retirer son rôle au compte superadmin.
 */
class ProtectedSuperAdminException extends LogicException
{
    public static function cannotDelete(): self
    {
        return new self('Le compte superadmin ne peut pas être supprimé.');
    }

    public static function cannotDeactivate(): self
    {
        return new self('Le compte superadmin ne peut pas être désactivé.');
    }

    public static function cannotLoseRole(): self
    {
        return new self('Le compte superadmin ne peut pas perdre son rôle.');
    }
}
