<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Rôles des comptes d'un tenant (stockés par spatie/laravel-permission).
 */
enum Role: string
{
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administratrice',
            self::Member => 'Membre',
        };
    }
}
