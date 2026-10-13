<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Comptes de l'équipe (config/rimef.php, clé team), créés ou rétablis dans chaque tenant, dans
 * tous les environnements. Relançable : nom, rôles et compte actif sont rétablis, le mot de passe
 * d'un compte existant n'est jamais écrasé.
 */
class TeamAccountsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array{email: ?string, password: ?string, first_name: string, last_name: string, roles: list<string>}> $accounts */
        $accounts = config('rimef.team', []);

        foreach ($accounts as $account) {
            if (blank($account['email'])) {
                continue;
            }

            $user = User::query()->firstOrNew(['email' => Str::lower($account['email'])]);
            $user->fill([
                'first_name' => $account['first_name'],
                'last_name' => $account['last_name'],
                'is_active' => true,
            ]);

            if (! $user->exists) {
                // Sans mot de passe configuré : aléatoire, à choisir via « Mot de passe oublié »
                $user->password = filled($account['password']) ? $account['password'] : Str::password(32);
                $user->email_verified_at = now();
            }

            $user->save();
            $user->assignRole($account['roles']);
        }
    }
}
