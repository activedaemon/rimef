<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Superadmin protégé (config/rimef.php), créé ou rétabli dans chaque tenant, dans tous
 * les environnements. Relançable : nom, rôle et compte actif sont rétablis, le mot de
 * passe d'un compte existant n'est jamais écrasé.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{email: ?string, password: ?string, first_name: string, last_name: string} $config */
        $config = config('rimef.superadmin');

        if (blank($config['email'])) {
            return;
        }

        $superAdmin = User::query()->firstOrNew(['email' => Str::lower($config['email'])]);
        $superAdmin->fill([
            'first_name' => $config['first_name'],
            'last_name' => $config['last_name'],
            'is_active' => true,
        ]);

        if (! $superAdmin->exists) {
            // Sans mot de passe configuré : aléatoire, à choisir via « Mot de passe oublié »
            $superAdmin->password = filled($config['password']) ? $config['password'] : Str::password(32);
            $superAdmin->email_verified_at = now();
        }

        $superAdmin->save();
        $superAdmin->syncRoles([Role::SuperAdmin->value]);
    }
}
