<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Liste des comptes de la rubrique Administration : tous les comptes du tenant,
 * invitations comprises (statut, rôle, recherche par nom ou email, tri, pagination).
 */
class AccountDirectory
{
    public const PER_PAGE = 12;

    public const PER_PAGE_OPTIONS = [12, 24, 48];

    /**
     * @param  array{q?: string|null, status?: list<string>, role?: list<string>, sort?: string|null, per_page?: int|string}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = User::query()->with([
            'roles',
            'memberProfile' => fn ($profile) => $profile->withCount(['expertises', 'zones']),
            'invitedBy',
        ]);

        $this->applySearch($query, (string) ($filters['q'] ?? ''));

        $statuses = array_map(AccountStatus::from(...), $filters['status'] ?? []);
        if ($statuses !== []) {
            $query->where(function (Builder $query) use ($statuses): void {
                foreach ($statuses as $status) {
                    $query->orWhere(fn (Builder $query) => $query->withAccountStatus($status));
                }
            });
        }

        if (($filters['role'] ?? []) !== []) {
            $query->role($filters['role']);
        }

        match ($filters['sort'] ?? 'name') {
            // Membre depuis : activation, ou création pour les comptes antérieurs ; invitations en dernier
            'created' => $query->orderBy('users.invitation_pending')
                ->orderByRaw('coalesce(users.activated_at, users.created_at) desc'),
            // Fiches les moins complètes d'abord ; comptes sans fiche (équipe, invitations) en dernier
            'completion' => $query
                ->orderByRaw('case when users.invitation_pending = 0 and exists (select 1 from model_has_roles mhr join roles r on r.id = mhr.role_id where mhr.model_id = users.id and mhr.model_type = ? and r.name = ?) then 0 else 1 end', [(new User)->getMorphClass(), Role::Member->value])
                ->orderByRaw('coalesce('.ProfileSummary::completionSql().', 0)'),
            // Jamais connectée en dernier
            'last_login' => $query->orderByRaw('users.last_login_at is null')->orderByDesc('users.last_login_at'),
            default => $query->orderBy('users.last_name'),
        };
        $query->orderBy('users.first_name')->orderBy('users.id');

        return $query->paginate((int) ($filters['per_page'] ?? self::PER_PAGE))->withQueryString();
    }

    /**
     * Comptes par valeur de filtre, filtres ignorés (compteurs des menus Statut et Rôle).
     *
     * @return array{total: int, status: list<array{value: string, label: string, count: int}>, role: list<array{value: string, label: string, count: int}>}
     */
    public function facets(): array
    {
        return [
            'total' => User::query()->count(),
            'status' => array_map(fn (AccountStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => User::query()->withAccountStatus($status)->count(),
            ], AccountStatus::cases()),
            'role' => array_map(fn (Role $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
                'count' => User::query()->role($role->value)->count(),
            ], [Role::Member, Role::Admin, Role::SuperAdmin]),
        ];
    }

    /**
     * Chaque mot doit se trouver dans le prénom, le nom ou l'email.
     *
     * @param  Builder<User>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $words = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';

            $query->where(fn (Builder $query) => $query->where('users.first_name', 'like', $like)
                ->orWhere('users.last_name', 'like', $like)
                ->orWhere('users.email', 'like', $like));
        }
    }
}
