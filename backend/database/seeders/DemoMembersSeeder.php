<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\Expertise;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Médiatrices fictives pour l'annuaire, en environnement local uniquement
 * (TenantDatabaseSeeder). Relançable : les comptes sont retrouvés par email.
 * Les comptes reçoivent un mot de passe aléatoire : ils ne servent pas à se connecter.
 */
class DemoMembersSeeder extends Seeder
{
    /**
     * prénom, nom, pays, organisation, disponible, expertises, langues, rôles supplémentaires
     *
     * @var list<array{0: string, 1: string, 2: string|null, 3: OrganizationType|null, 4: bool, 5: list<string>, 6: list<string>, 7?: list<Role>}>
     */
    private const MEMBERS = [
        ['Aminata', 'Diallo', 'SN', OrganizationType::CivilSociety, false, ['Médiation communautaire', 'Femmes, paix et sécurité'], ['fr', 'en', 'wo']],
        ['Fatou', 'Ndiaye', 'CI', OrganizationType::InternationalOrganization, false, ['Prévention des conflits', 'Dialogue interreligieux'], ['fr', 'en']],
        ['Leïla', 'Bouzid', 'MA', OrganizationType::LocalAuthority, false, ['Gouvernance locale', 'Jeunesse et paix'], ['fr', 'ar', 'en']],
        ['Mariam', 'Keita', 'ML', OrganizationType::CivilSociety, true, ['Médiation et justice', 'Société civile'], ['fr', 'en', 'bm']],
        ['Claire', 'Dubois', 'FR', OrganizationType::Diplomacy, false, ['Médiation internationale', 'Multilatéralisme'], ['fr', 'en']],
        ['Sarah', 'Mensah', 'CA', OrganizationType::InternationalOrganization, false, ['Femmes, paix et sécurité', 'Policy & plaidoyer'], ['fr', 'en']],
        ['Andrée', 'Niyonsaba', 'BI', OrganizationType::CivilSociety, true, ['Cohésion sociale', 'Médiation communautaire'], ['fr', 'en', 'rn']],
        ['Hélène', 'Martin', 'CH', OrganizationType::Research, false, ['Recherche et formation', 'Dialogue inclusif'], ['fr', 'en', 'de']],
        ['Nadia', 'Benali', 'TN', OrganizationType::CivilSociety, true, ['Dialogue inclusif', 'Jeunesse et paix'], ['fr', 'ar']],
        ['Josiane', 'Mbarga', 'CM', OrganizationType::LocalAuthority, false, ['Gouvernance locale', 'Cohésion sociale'], ['fr', 'en']],
        ['Aïssatou', 'Bah', 'GN', OrganizationType::CivilSociety, false, ['Médiation communautaire', 'Prévention des conflits'], ['fr', 'ff']],
        ['Grace', 'Uwimana', 'RW', OrganizationType::InternationalOrganization, true, ['Médiation et justice', 'Femmes, paix et sécurité'], ['fr', 'en', 'rw']],
        ['Marie-Claude', 'Tremblay', 'CA', OrganizationType::Research, false, ['Recherche et formation', 'Médiation internationale'], ['fr', 'en']],
        ['Rokia', 'Coulibaly', 'BF', OrganizationType::CivilSociety, true, ['Femmes, paix et sécurité', 'Société civile'], ['fr', 'bm']],
        ['Sophie', 'Lambert', 'BE', OrganizationType::Diplomacy, false, ['Multilatéralisme', 'Policy & plaidoyer'], ['fr', 'en', 'es']],
        ['Hanane', 'El Amrani', 'MA', OrganizationType::Research, false, ['Dialogue interreligieux', 'Recherche et formation'], ['fr', 'ar', 'en']],
        ['Esther', 'Mukendi', 'CD', OrganizationType::CivilSociety, true, ['Cohésion sociale', 'Prévention des conflits'], ['fr', 'ln', 'sw']],
        ['Nathalie', 'Joseph', 'HT', OrganizationType::LocalAuthority, false, ['Médiation communautaire', 'Gouvernance locale'], ['fr', 'ht']],
        ['Lina', 'Haddad', 'LB', OrganizationType::InternationalOrganization, false, ['Dialogue interreligieux', 'Médiation internationale'], ['fr', 'ar', 'en']],
        ['Christine', 'Rakoto', 'MG', OrganizationType::CivilSociety, false, ['Jeunesse et paix', 'Cohésion sociale'], ['fr', 'mg']],
        // Rôles cumulés : administratrice et médiatrice.
        ['Isabelle', 'Moreau', 'FR', OrganizationType::Research, true, ['Dialogue inclusif', 'Médiation internationale'], ['fr', 'en'], [Role::Admin]],
        // Médiatrice sans profil rempli : la carte n'affiche que le nom.
        ['Mireille', 'Kouassi', null, null, false, [], []],
    ];

    public function run(): void
    {
        $expertises = Expertise::query()->pluck('id', 'name');

        foreach (self::MEMBERS as $member) {
            [$firstName, $lastName, $country, $organization, $available, $memberExpertises, $languages] = $member;

            $user = User::query()->firstOrCreate(
                ['email' => Str::slug("{$firstName}.{$lastName}", '.').'@example.org'],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make(Str::random(32)),
                ],
            );
            $user->assignRole([Role::Member, ...($member[7] ?? [])]);

            if ($country === null) {
                continue;
            }

            $profile = $user->memberProfile()->updateOrCreate([], [
                'country_code' => $country,
                'organization_type' => $organization,
                'is_available' => $available,
            ]);

            $profile->expertises()->sync(collect($memberExpertises)->mapWithKeys(
                fn (string $name, int $position): array => [$expertises[$name] => ['position' => $position]],
            )->all());

            $profile->syncLanguages($languages);
        }
    }
}
