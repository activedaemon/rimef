<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Rôles des comptes d'un tenant (stockés par spatie/laravel-permission).
 *
 * Le superadmin passe toutes les autorisations (Gate::before) et son compte est protégé :
 * ni supprimable, ni désactivable, ni privable de son rôle (voir User).
 */
enum Role: string
{
    case SuperAdmin = 'superadmin';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Superadministrateur',
            self::Admin => 'Administratrice',
            self::Member => 'Membre',
        };
    }
}
