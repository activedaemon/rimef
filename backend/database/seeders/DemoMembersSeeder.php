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

    /**
     * Fiches des médiatrices fictives, par slug : ville, fonction, citation, à propos,
     * début d'expérience, publics accompagnés, zones d'intervention.
     * Les personnes réelles (fondatrices, Olivia Caeymaex) n'en ont pas : leur fiche
     * sera complétée avec les informations qu'elles fourniront.
     *
     * @var array<string, array{0: string, 1: string, 2: string|null, 3: string, 4: int, 5: string|null, 6: list<string>}>
     */
    private const PROFILES = [
        'aminata-diallo' => ['Dakar', 'Conseillère en paix et sécurité', 'Favoriser le dialogue inclusif pour des sociétés plus résilientes.', 'Aminata Diallo est médiatrice et conseillère en paix, spécialisée dans la prévention des conflits et le dialogue inclusif en Afrique de l’Ouest. Elle accompagne des processus de médiation impliquant des femmes, des jeunes et des leaders communautaires, et conseille plusieurs organisations sur les questions de paix et de gouvernance.', 2011, 'Femmes, jeunes, leaders communautaires', ['SN', 'ML', 'MR', 'CI']],
        'fatou-ndiaye' => ['Abidjan', 'Chargée de programme prévention des conflits', 'Prévenir, c’est déjà réparer.', 'Fatou Ndiaye coordonne des programmes de prévention des conflits et de dialogue interreligieux au sein d’une organisation internationale.', 2014, 'Responsables religieux, autorités locales', ['CI', 'BF', 'GN']],
        'leila-bouzid' => ['Rabat', 'Médiatrice territoriale', null, 'Leïla Bouzid accompagne les collectivités dans la résolution des différends locaux et la participation des jeunes à la vie publique.', 2016, 'Jeunes, élus locaux', ['MA', 'TN']],
        'mariam-keita' => ['Bamako', 'Médiatrice judiciaire', 'La justice commence par l’écoute.', 'Mariam Keita intervient dans la médiation judiciaire et l’accès au droit, en lien étroit avec les organisations de la société civile malienne.', 2009, 'Justiciables, associations de femmes', ['ML', 'NE', 'BF']],
        'claire-dubois' => ['Paris', 'Diplomate, conseillère médiation', null, 'Claire Dubois a servi dans plusieurs postes diplomatiques et conseille aujourd’hui sur les processus de médiation multilatéraux.', 2005, 'Délégations, organisations régionales', ['FR', 'LB', 'TD']],
        'sarah-mensah' => ['Montréal', 'Spécialiste Femmes, paix et sécurité', 'Pas de paix durable sans les femmes à la table.', 'Sarah Mensah travaille sur la mise en œuvre de l’agenda Femmes, paix et sécurité et sur le plaidoyer auprès des institutions internationales.', 2012, 'Décideuses, réseaux de femmes', ['CA', 'HT', 'CD']],
        'andree-niyonsaba' => ['Bujumbura', 'Médiatrice communautaire', 'Recoudre le tissu social, un fil après l’autre.', 'Andrée Niyonsaba anime des espaces de dialogue communautaire pour la cohésion sociale dans la région des Grands Lacs.', 2010, 'Communautés rurales, femmes', ['BI', 'RW', 'CD']],
        'helene-martin' => ['Genève', 'Chercheuse et formatrice', null, 'Hélène Martin conduit des recherches sur le dialogue inclusif et forme des médiatrices aux méthodes de facilitation.', 2008, 'Étudiantes, praticiennes', ['CH', 'FR', 'BE']],
        'nadia-benali' => ['Tunis', 'Facilitatrice de dialogue', 'Créer les conditions pour que chacun soit entendu.', 'Nadia Benali facilite des dialogues entre jeunes, autorités et société civile, en Tunisie et dans la région.', 2015, 'Jeunes, société civile', ['TN', 'LY', 'DZ']],
        'josiane-mbarga' => ['Yaoundé', 'Conseillère en gouvernance locale', null, 'Josiane Mbarga accompagne les collectivités camerounaises dans la prévention des tensions locales.', 2013, 'Élus locaux, chefferies traditionnelles', ['CM', 'CF']],
        'aissatou-bah' => ['Conakry', 'Médiatrice communautaire', null, 'Aïssatou Bah intervient dans la médiation des conflits fonciers et communautaires en Guinée.', 2017, 'Communautés rurales', ['GN', 'SL', 'LR']],
        'grace-uwimana' => ['Kigali', 'Experte justice transitionnelle', 'Se souvenir pour reconstruire.', 'Grace Uwimana conseille des programmes de justice transitionnelle et de réconciliation.', 2007, 'Victimes, institutions judiciaires', ['RW', 'BI', 'SS']],
        'marie-claude-tremblay' => ['Québec', 'Professeure en résolution des conflits', null, 'Marie-Claude Tremblay enseigne la résolution des conflits et dirige des recherches sur la médiation internationale.', 2004, 'Étudiantes, chercheuses', ['CA', 'HT']],
        'rokia-coulibaly' => ['Ouagadougou', 'Coordinatrice de réseau associatif', 'Les femmes sont déjà médiatrices ; il faut les reconnaître.', 'Rokia Coulibaly coordonne un réseau d’associations de femmes engagées dans la paix au Sahel.', 2012, 'Associations de femmes', ['BF', 'ML', 'NE']],
        'sophie-lambert' => ['Bruxelles', 'Conseillère politique', null, 'Sophie Lambert suit les questions de médiation et de plaidoyer au niveau européen.', 2010, 'Institutions européennes', ['BE', 'LU', 'FR']],
        'hanane-el-amrani' => ['Casablanca', 'Chercheuse en dialogue interreligieux', null, 'Hanane El Amrani étudie le rôle des acteurs religieux dans la prévention des conflits.', 2014, 'Responsables religieux, chercheuses', ['MA', 'SN']],
        'esther-mukendi' => ['Kinshasa', 'Médiatrice pour la cohésion sociale', 'La paix se construit au quotidien.', 'Esther Mukendi mène des actions de cohésion sociale et de prévention des violences dans l’est de la RDC.', 2011, 'Femmes, jeunes, déplacés', ['CD', 'CG']],
        'nathalie-joseph' => ['Port-au-Prince', 'Médiatrice municipale', null, 'Nathalie Joseph accompagne les quartiers dans la médiation des conflits de voisinage et de gouvernance locale.', 2016, 'Habitants, élus locaux', ['HT', 'DO']],
        'lina-haddad' => ['Beyrouth', 'Chargée de médiation', null, 'Lina Haddad travaille sur des initiatives de dialogue interreligieux et de médiation au Moyen-Orient.', 2013, 'Communautés, organisations internationales', ['LB', 'SY', 'JO']],
        'christine-rakoto' => ['Antananarivo', 'Animatrice jeunesse et paix', null, 'Christine Rakoto développe des programmes d’éducation à la paix pour les jeunes à Madagascar.', 2018, 'Jeunes, enseignants', ['MG', 'KM']],
        'isabelle-moreau' => ['Lyon', 'Chercheuse et médiatrice', 'Le dialogue est une discipline.', 'Isabelle Moreau partage son temps entre la recherche sur le dialogue inclusif et l’accompagnement de processus de médiation internationale.', 2006, 'Praticiennes, institutions', ['FR', 'CH', 'TN']],
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

            [$city, $jobTitle, $tagline, $bio, $experienceSince, $audiences, $zones] = self::PROFILES[$user->slug]
                ?? [null, null, null, null, null, null, []];

            /** @var MemberProfile $profile */
            $profile = $user->memberProfile()->updateOrCreate([], [
                'country_code' => $country,
                'city' => $city,
                'organization_type' => $organization,
                'job_title' => $jobTitle,
                'tagline' => $tagline,
                'bio' => $bio,
                'experience_since' => $experienceSince,
                'audiences' => $audiences,
                'is_available' => $available,
            ]);

            $profile->zones()->delete();
            $profile->zones()->createMany(collect($zones)->map(
                fn (string $code, int $position): array => ['country_code' => $code, 'position' => $position],
            )->all());

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
