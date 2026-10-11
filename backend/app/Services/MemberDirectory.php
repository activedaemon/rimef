<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrganizationType;
use App\Enums\Region;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Annuaire du réseau : comptes actifs ayant le rôle member (cumulable avec admin).
 */
class MemberDirectory
{
    public const PER_PAGE = 12;

    /**
     * Pour chaque médiatrice : `is_favorite` (marque-page de $viewer), son prochain événement et
     * `conversation_id`, la conversation engagée avec $viewer (fenêtre « Nouveau message »).
     *
     * @param  array{q?: string|null, expertise?: list<int>, region?: list<string>, organization?: list<string>, available?: bool, favorites?: bool, upcoming?: bool, sort?: string|null}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function search(array $filters, User $viewer): LengthAwarePaginator
    {
        $query = $this->members()
            ->leftJoin('member_profiles', 'member_profiles.user_id', '=', 'users.id')
            ->select('users.*')
            ->addSelect([
                'next_event_id' => $this->nextEvent()->select('events.id'),
                'next_event_at' => $this->nextEvent()->select('events.starts_at'),
                'conversation_id' => $this->conversationWith($viewer),
            ])
            ->withExists(['favoredBy as is_favorite' => fn (Builder $favorite) => $favorite->whereKey($viewer->getKey())])
            ->with(['memberProfile.expertises', 'nextEvent']);

        $this->applySearch($query, (string) ($filters['q'] ?? ''));
        $this->applyFilters($query, $filters, $viewer);
        $this->applySort($query, $filters['sort'] ?? null);

        return $query->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * Nombre total de médiatrices, filtres ignorés (« 3 médiatrices sur 124 »).
     */
    public function total(): int
    {
        return $this->members()->count();
    }

    /**
     * Valeurs proposées par chaque filtre, avec leur nombre de médiatrices
     * (seules les valeurs portées par au moins une médiatrice sont listées).
     *
     * @return array{expertise: list<array{value: int, label: string, count: int}>, region: list<array{value: string, label: string, count: int}>, organization: list<array{value: string, label: string, count: int}>}
     */
    public function facets(): array
    {
        $profiles = DB::table('member_profiles')->whereIn('user_id', $this->members()->select('users.id'));

        $countries = (clone $profiles)->whereNotNull('country_code')
            ->groupBy('country_code')->pluck(DB::raw('count(*)'), 'country_code');

        $regionCounts = [];
        foreach ($countries as $code => $count) {
            $region = DirectoryCatalog::regionOf($code);
            if ($region !== null) {
                $regionCounts[$region->value] = ($regionCounts[$region->value] ?? 0) + (int) $count;
            }
        }

        $organizations = (clone $profiles)->whereNotNull('organization_type')
            ->groupBy('organization_type')->pluck(DB::raw('count(*)'), 'organization_type');

        $expertises = Expertise::query()
            ->withCount(['memberProfiles' => fn (Builder $query) => $query->whereIn('user_id', $this->members()->select('users.id'))])
            ->get()
            ->filter(fn (Expertise $expertise): bool => $expertise->member_profiles_count > 0)
            ->sortBy(fn (Expertise $expertise): string => Str::ascii($expertise->name));

        return [
            'expertise' => $expertises->map(fn (Expertise $expertise): array => [
                'value' => $expertise->id,
                'label' => $expertise->name,
                'count' => $expertise->member_profiles_count,
            ])->values()->all(),
            'region' => $this->options(
                $regionCounts,
                fn (string $value): string => Region::from($value)->label(),
            ),
            'organization' => $this->options(
                $organizations->all(),
                fn (string $value): string => OrganizationType::from($value)->label(),
            ),
        ];
    }

    /**
     * La médiatrice figure dans l'annuaire (compte actif ayant le rôle member).
     */
    public function isListed(User $user): bool
    {
        return $this->members()->whereKey($user->getKey())->exists();
    }

    /**
     * Premier événement à venir de la médiatrice de la ligne courante (sous-requête).
     *
     * @return Builder<Event>
     */
    private function nextEvent(): Builder
    {
        return Event::query()
            ->join('event_user', 'event_user.event_id', '=', 'events.id')
            ->whereColumn('event_user.user_id', 'users.id')
            ->where('events.starts_at', '>=', now())
            ->orderBy('events.starts_at')
            ->orderBy('events.id')
            ->limit(1);
    }

    /**
     * @return Builder<User>
     */
    /**
     * Conversation de $viewer avec la médiatrice de la ligne, si elles ont déjà échangé.
     */
    private function conversationWith(User $viewer): QueryBuilder
    {
        return DB::table('conversation_user as mine')
            ->select('mine.conversation_id')
            ->join('conversation_user as theirs', 'theirs.conversation_id', '=', 'mine.conversation_id')
            ->where('mine.user_id', $viewer->getKey())
            ->whereColumn('theirs.user_id', 'users.id')
            ->whereColumn('theirs.user_id', '!=', 'mine.user_id')
            ->whereExists(fn (QueryBuilder $messages) => $messages->from('messages')
                ->whereColumn('messages.conversation_id', 'mine.conversation_id'))
            ->limit(1);
    }

    private function members(): Builder
    {
        return User::query()
            ->role(Role::Member->value)
            ->where('users.is_active', true);
    }

    /**
     * Chaque mot doit apparaître dans le nom, le pays, l'organisation, ou une expertise.
     *
     * @param  Builder<User>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $words = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';
            $countryCodes = DirectoryCatalog::codesMatching(DirectoryCatalog::countryNames(), $word);
            $organizations = DirectoryCatalog::codesMatching(
                collect(OrganizationType::cases())->mapWithKeys(fn (OrganizationType $type) => [$type->value => $type->label()])->all(),
                $word,
            );

            $query->where(function (Builder $query) use ($like, $countryCodes, $organizations): void {
                $query->where('users.first_name', 'like', $like)
                    ->orWhere('users.last_name', 'like', $like)
                    ->orWhereIn('member_profiles.country_code', $countryCodes)
                    ->orWhereIn('member_profiles.organization_type', $organizations)
                    ->orWhereExists(fn (QueryBuilder $sub) => $sub->from('expertise_member_profile')
                        ->join('expertises', 'expertises.id', '=', 'expertise_member_profile.expertise_id')
                        ->whereColumn('expertise_member_profile.member_profile_id', 'member_profiles.id')
                        ->where('expertises.name', 'like', $like));
            });
        }
    }

    /**
     * Valeurs cochées d'un même filtre : l'une ou l'autre ; filtres différents : tous à la fois.
     *
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, User $viewer): void
    {
        if (! empty($filters['expertise'])) {
            $query->whereExists(fn (QueryBuilder $sub) => $sub->from('expertise_member_profile')
                ->whereColumn('expertise_member_profile.member_profile_id', 'member_profiles.id')
                ->whereIn('expertise_member_profile.expertise_id', $filters['expertise']));
        }

        if (! empty($filters['region'])) {
            $query->whereIn('member_profiles.country_code', DirectoryCatalog::countryCodesIn($filters['region']));
        }

        if (! empty($filters['organization'])) {
            $query->whereIn('member_profiles.organization_type', $filters['organization']);
        }

        if (! empty($filters['available'])) {
            $query->where('member_profiles.is_available', true);
        }

        if (! empty($filters['favorites'])) {
            $query->whereExists(fn (QueryBuilder $sub) => $sub->from('favorites')
                ->whereColumn('favorites.member_id', 'users.id')
                ->where('favorites.user_id', $viewer->getKey()));
        }

        if (! empty($filters['upcoming'])) {
            $query->whereExists(fn (QueryBuilder $sub) => $sub->from('event_user')
                ->join('events', 'events.id', '=', 'event_user.event_id')
                ->whereColumn('event_user.user_id', 'users.id')
                ->where('events.starts_at', '>=', now()));
        }
    }

    /**
     * Par nom (par défaut), par pays (ordre alphabétique des noms en français) ou par prochain
     * événement (le plus proche d'abord, médiatrices sans événement à la fin).
     *
     * @param  Builder<User>  $query
     */
    private function applySort(Builder $query, ?string $sort): void
    {
        if ($sort === 'event') {
            $query->orderByRaw('next_event_at IS NULL')->orderBy('next_event_at');
        }

        if ($sort === 'country') {
            $codes = collect(DirectoryCatalog::countryNames())
                ->sortBy(fn (string $name): string => Str::ascii($name))
                ->keys()
                ->all();

            $cases = implode(' ', array_fill(0, count($codes), 'WHEN ? THEN ?'));
            $bindings = [];
            foreach ($codes as $rank => $code) {
                array_push($bindings, $code, $rank);
            }

            $query->orderByRaw('member_profiles.country_code IS NULL')
                ->orderByRaw("CASE member_profiles.country_code {$cases} ELSE ".count($codes).' END', $bindings);
        }

        $query->orderBy('users.last_name')->orderBy('users.first_name')->orderBy('users.id');
    }

    /**
     * @param  array<string, int|string>  $counts  valeur => nombre
     * @param  callable(string): string  $label
     * @return list<array{value: string, label: string, count: int}>
     */
    private function options(array $counts, callable $label): array
    {
        return collect($counts)
            ->map(fn (int|string $count, string $value): array => ['value' => $value, 'label' => $label($value), 'count' => (int) $count])
            ->sortBy(fn (array $option): string => Str::ascii($option['label']))
            ->values()
            ->all();
    }
}
