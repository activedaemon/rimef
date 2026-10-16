<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;

/**
 * Date de dernière connexion (liste des comptes de l'administration).
 * Écrite directement : une connexion ne change pas la date de mise à jour du compte.
 */
class RecordLastLogin
{
    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            DB::table('users')->where('id', $event->user->getKey())->update(['last_login_at' => now()]);
        }
    }
}
