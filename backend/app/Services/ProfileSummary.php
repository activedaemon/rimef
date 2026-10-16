<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\MemberProfile;
use App\Models\User;

/**
 * Carte « Fiche dans le réseau » de l'administration : état de la fiche d'une médiatrice
 * (complétude et éléments manquants, visibilité dans l'annuaire, dernière modification) et sa
 * participation au réseau (événements à venir, ressources).
 */
class ProfileSummary
{
    /**
     * Éléments qui font une fiche complète, avec leur libellé (« Manquent : photo, biographie »).
     *
     * @var array<string, string>
     */
    public const ITEMS = [
        'photo' => 'photo',
        'job_title' => 'fonction',
        'organization' => 'organisation',
        'country' => 'pays',
        'bio' => 'biographie',
        'expertises' => 'expertises',
        'zones' => 'zones d’intervention',
        'links' => 'liens',
    ];

    public function __construct(
        private readonly EventDirectory $events,
        private readonly ResourceDirectory $resources,
    ) {}

    /**
     * Null pour un compte sans rôle Membre ou une invitation (pas encore de fiche).
     *
     * @return array{completion: int, missing: list<string>, visible: bool, updated_at: string|null, upcoming_events: int, resources: int}|null
     */
    public function for(User $account): ?array
    {
        if (! $account->hasRole(Role::Member->value) || $account->invitation_pending) {
            return null;
        }

        $profile = $account->memberProfile()->withCount(['expertises', 'zones'])->first();
        $completion = self::completion($profile);

        return [
            'completion' => $completion['percent'],
            'missing' => $completion['missing'],
            'visible' => $account->accountStatus() === AccountStatus::Active,
            'updated_at' => $profile?->updated_at?->toIso8601String(),
            'upcoming_events' => $this->events->upcomingFor($account, PHP_INT_MAX)->count(),
            'resources' => $this->resources->contributionsOf($account)->count(),
        ];
    }

    /**
     * Complétude d'une fiche (profil chargé avec withCount expertises et zones) : pourcentage et
     * éléments manquants. Même définition que completionSql(), qui sert au tri de la liste.
     *
     * @return array{percent: int, missing: list<string>}
     */
    public static function completion(?MemberProfile $profile): array
    {
        $filled = self::filled($profile);

        return [
            'percent' => (int) round(100 * count(array_filter($filled)) / count(self::ITEMS)),
            'missing' => array_values(array_map(
                fn (string $item): string => self::ITEMS[$item],
                array_keys(array_filter($filled, fn (bool $done): bool => ! $done)),
            )),
        ];
    }

    /**
     * Nombre d'éléments remplis de la fiche de users.id, en SQL (tri de la liste des comptes) ;
     * 0 sans fiche. Mêmes éléments que filled().
     */
    public static function completionSql(): string
    {
        $filled = static fn (string $condition): string => "(case when {$condition} then 1 else 0 end)";

        return '(select '.implode(' + ', [
            $filled('mp.photo_path is not null'),
            $filled("coalesce(mp.job_title, '') <> ''"),
            $filled("mp.organization_type is not null or coalesce(mp.organization_name, '') <> ''"),
            $filled("coalesce(mp.country_code, '') <> ''"),
            $filled("coalesce(mp.bio, '') <> ''"),
            $filled('exists (select 1 from expertise_member_profile emp where emp.member_profile_id = mp.id)'),
            $filled('exists (select 1 from member_zones mz where mz.member_profile_id = mp.id)'),
            $filled('mp.links is not null'),
        ]).' from member_profiles mp where mp.user_id = users.id)';
    }

    /**
     * @return array<string, bool>
     */
    private static function filled(?MemberProfile $profile): array
    {
        return [
            'photo' => $profile?->photo_path !== null,
            'job_title' => filled($profile?->job_title),
            'organization' => $profile?->organization_type !== null || filled($profile?->organization_name),
            'country' => filled($profile?->country_code),
            'bio' => filled($profile?->bio),
            'expertises' => (int) ($profile?->expertises_count ?? 0) > 0,
            'zones' => (int) ($profile?->zones_count ?? 0) > 0,
            'links' => $profile !== null && $profile->displayedLinks() !== [],
        ];
    }
}
