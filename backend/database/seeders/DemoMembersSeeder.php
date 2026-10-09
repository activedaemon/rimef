<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\Expertise;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Médiatrices de l'annuaire (fondatrices et profils fictifs), en environnement local uniquement
 * (TenantDatabaseSeeder). Relançable : les comptes sont retrouvés par email.
 * Les comptes reçoivent un mot de passe aléatoire : ils ne servent pas à se connecter.
 * Photos : member-photos/<prénom-nom>.webp.
 */
class DemoMembersSeeder extends Seeder
{
    /**
     * prénom, nom, pays, organisation, disponible, expertises, rôles supplémentaires
     *
     * @var list<array{0: string, 1: string, 2: string|null, 3: OrganizationType|null, 4: bool, 5: list<string>, 6?: list<Role>}>
     */
    private const MEMBERS = [
        ['Aminata', 'Diallo', 'SN', OrganizationType::CivilSociety, false, ['Médiation communautaire', 'Femmes, paix et sécurité']],
        ['Fatou', 'Ndiaye', 'CI', OrganizationType::InternationalOrganization, false, ['Prévention des conflits', 'Dialogue interreligieux']],
        ['Leïla', 'Bouzid', 'MA', OrganizationType::LocalAuthority, false, ['Gouvernance locale', 'Jeunesse et paix']],
        ['Mariam', 'Keita', 'ML', OrganizationType::CivilSociety, true, ['Médiation et justice', 'Société civile']],
        ['Claire', 'Dubois', 'FR', OrganizationType::Diplomacy, false, ['Médiation internationale', 'Multilatéralisme']],
        ['Sarah', 'Mensah', 'CA', OrganizationType::InternationalOrganization, false, ['Femmes, paix et sécurité', 'Policy & plaidoyer']],
        ['Andrée', 'Niyonsaba', 'BI', OrganizationType::CivilSociety, true, ['Cohésion sociale', 'Médiation communautaire']],
        ['Hélène', 'Martin', 'CH', OrganizationType::Research, false, ['Recherche et formation', 'Dialogue inclusif']],
        ['Nadia', 'Benali', 'TN', OrganizationType::CivilSociety, true, ['Dialogue inclusif', 'Jeunesse et paix']],
        ['Josiane', 'Mbarga', 'CM', OrganizationType::LocalAuthority, false, ['Gouvernance locale', 'Cohésion sociale']],
        ['Aïssatou', 'Bah', 'GN', OrganizationType::CivilSociety, false, ['Médiation communautaire', 'Prévention des conflits']],
        ['Grace', 'Uwimana', 'RW', OrganizationType::InternationalOrganization, true, ['Médiation et justice', 'Femmes, paix et sécurité']],
        ['Marie-Claude', 'Tremblay', 'CA', OrganizationType::Research, false, ['Recherche et formation', 'Médiation internationale']],
        ['Rokia', 'Coulibaly', 'BF', OrganizationType::CivilSociety, true, ['Femmes, paix et sécurité', 'Société civile']],
        ['Sophie', 'Lambert', 'BE', OrganizationType::Diplomacy, false, ['Multilatéralisme', 'Policy & plaidoyer']],
        ['Hanane', 'El Amrani', 'MA', OrganizationType::Research, false, ['Dialogue interreligieux', 'Recherche et formation']],
        ['Esther', 'Mukendi', 'CD', OrganizationType::CivilSociety, true, ['Cohésion sociale', 'Prévention des conflits']],
        ['Nathalie', 'Joseph', 'HT', OrganizationType::LocalAuthority, false, ['Médiation communautaire', 'Gouvernance locale']],
        ['Lina', 'Haddad', 'LB', OrganizationType::InternationalOrganization, false, ['Dialogue interreligieux', 'Médiation internationale']],
        ['Christine', 'Rakoto', 'MG', OrganizationType::CivilSociety, false, ['Jeunesse et paix', 'Cohésion sociale']],
        // Rôles cumulés : administratrice et médiatrice.
        ['Isabelle', 'Moreau', 'FR', OrganizationType::Research, true, ['Dialogue inclusif', 'Médiation internationale'], [Role::Admin]],
        // Administratrice et médiatrice (personne réelle, photo exclue de git).
        ['Olivia', 'Caeymaex', 'BE', OrganizationType::CivilSociety, false, ['Médiation internationale', 'Policy & plaidoyer'], [Role::Admin]],
        // Fondatrices du réseau (personnes réelles, profils d'après leurs pages publiques) ;
        // leurs photos sont exclues de git.
        ['Marie-Joëlle', 'Zahar', 'CA', OrganizationType::Research, false, ['Médiation internationale', 'Recherche et formation', 'Prévention des conflits']],
        ['Achta', 'Djibrine Sy', 'TD', OrganizationType::CivilSociety, false, ['Femmes, paix et sécurité', 'Médiation communautaire', 'Cohésion sociale']],
        ['Nelly Godelive', 'Mbangu', 'CD', OrganizationType::CivilSociety, false, ['Médiation et justice', 'Femmes, paix et sécurité']],
        ['Delphine', 'Borione', 'FR', OrganizationType::Diplomacy, false, ['Multilatéralisme', 'Policy & plaidoyer']],
        ['Fatima', 'Maïga', 'ML', OrganizationType::CivilSociety, false, ['Femmes, paix et sécurité', 'Policy & plaidoyer']],
        ['Esther', 'Omam', 'CM', OrganizationType::CivilSociety, false, ['Médiation communautaire', 'Prévention des conflits', 'Femmes, paix et sécurité']],
        ['Marguerite', 'Yoli-Bi Koné', 'CI', OrganizationType::CivilSociety, false, ['Prévention des conflits', 'Cohésion sociale', 'Femmes, paix et sécurité']],
        ['Catherine', 'Samba-Panza', 'CF', OrganizationType::CivilSociety, false, ['Médiation internationale', 'Prévention des conflits', 'Femmes, paix et sécurité']],
        ['Kalinda', 'Magloire', 'HT', OrganizationType::CivilSociety, false, ['Prévention des conflits', 'Femmes, paix et sécurité', 'Société civile']],
        // Médiatrice sans profil rempli : la carte n'affiche que le nom (et sa photo).
        ['Mireille', 'Kouassi', null, null, false, []],
    ];

    public function run(): void
    {
        $expertises = Expertise::query()->pluck('id', 'name');

        foreach (self::MEMBERS as $member) {
            [$firstName, $lastName, $country, $organization, $available, $memberExpertises] = $member;

            $user = User::query()->firstOrCreate(
                ['email' => Str::slug("{$firstName}.{$lastName}", '.').'@example.org'],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => Hash::make(Str::random(32)),
                ],
            );
            $user->assignRole([Role::Member, ...($member[6] ?? [])]);

            /** @var MemberProfile $profile */
            $profile = $user->memberProfile()->updateOrCreate([], [
                'country_code' => $country,
                'organization_type' => $organization,
                'is_available' => $available,
            ]);

            $profile->expertises()->sync(collect($memberExpertises)->mapWithKeys(
                fn (string $name, int $position): array => [$expertises[$name] => ['position' => $position]],
            )->all());

            $this->attachPhoto($profile, Str::slug("{$firstName} {$lastName}"));
        }
    }

    /**
     * Photo de la médiatrice, si member-photos/<prénom-nom>.webp existe (photos de personnes
     * réelles exclues de git) : copiée dans le stockage privé du tenant, comme une photo
     * envoyée depuis son profil.
     */
    private function attachPhoto(MemberProfile $profile, string $slug): void
    {
        $source = database_path("seeders/member-photos/{$slug}.webp");

        if (! is_file($source)) {
            return;
        }

        $path = "members/{$profile->id}.webp";
        $contents = (string) file_get_contents($source);
        $disk = Storage::disk('local');

        if ($profile->photo_path === $path && $disk->exists($path) && $disk->get($path) === $contents) {
            return;
        }

        $disk->put($path, $contents);
        // Nouvelle date de mise à jour = nouvelle URL de la photo : le cache navigateur est renouvelé
        $profile->photo_path = $path;
        $profile->touch();
    }
}
