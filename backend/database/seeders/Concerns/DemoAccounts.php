<?php

declare(strict_types=1);

namespace Database\Seeders\Concerns;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Comptes des seeders de démonstration, désignés par « prénom.nom » (aminata.diallo) : adresse
 * fictive en @example.org, sauf pour les comptes de l'équipe (config/rimef.php, clé team), qui
 * gardent leur vraie adresse pour ne pas exister en double.
 */
trait DemoAccounts
{
    /**
     * « olivia.caeymaex » → ocaeymaex@c-r.org ; « aminata.diallo » → aminata.diallo@example.org.
     */
    protected function demoEmail(string $name): string
    {
        foreach (config('rimef.team', []) as $account) {
            if (filled($account['email']) && Str::slug("{$account['first_name']}.{$account['last_name']}", '.') === $name) {
                return Str::lower($account['email']);
            }
        }

        return "{$name}@example.org";
    }

    /**
     * Identifiants des comptes, par « prénom.nom ».
     *
     * @param  list<string>  $names
     * @return Collection<string, int>
     */
    protected function demoMemberIds(array $names): Collection
    {
        $emails = collect($names)->unique()->mapWithKeys(fn (string $name): array => [$this->demoEmail($name) => $name]);
        $ids = User::query()->whereIn('email', $emails->keys())->pluck('id', 'email');

        return $ids->mapWithKeys(fn (int $id, string $email): array => [$emails[$email] => $id]);
    }
}
