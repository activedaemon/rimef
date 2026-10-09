<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrganizationType;
use App\Enums\Region;
use App\Enums\Role;
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
     * @param  array{q?: string|null, expertise?: list<int>, region?: list<string>, language?: list<string>, organization?: list<string>, available?: bool, sort?: string|null}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->members()
            ->leftJoin('member_profiles', 'member_profiles.user_id', '=', 'users.id')
            ->select('users.*')
            ->with(['memberProfile.expertises', 'memberProfile.languages']);

        $this->applySearch($query, (string) ($filters['q'] ?? ''));
        $this->applyFilters($query, $filters);
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
     * @return array{expertise: list<array{value: int, label: string, count: int}>, region: list<array{value: string, label: string, count: int}>, language: list<array{value: string, label: string, count: int}>, organization: list<array{value: string, label: string, count: int}>}
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

        $languages = DB::table('member_languages')
            ->whereIn('member_profile_id', (clone $profiles)->select('id'))
            ->groupBy('language_code')->pluck(DB::raw('count(*)'), 'language_code');

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
            'language' => $this->options(
                $languages->all(),
                fn (string $value): string => DirectoryCatalog::languageName($value),
            ),
            'organization' => $this->options(
                $organizations->all(),
                fn (string $value): string => OrganizationType::from($value)->label(),
            ),
        ];
    }

    /**
     * @return Builder<User>
     */
    private function members(): Builder
    {
        return User::query()
            ->role(Role::Member->value)
            ->where('users.is_active', true);
    }

    /**
     * Chaque mot doit apparaître dans le nom, le pays, l'organisation, une expertise ou une langue.
     *
     * @param  Builder<User>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $words = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';
            $countryCodes = DirectoryCatalog::codesMatching(DirectoryCatalog::countryNames(), $word);
            $languageCodes = DirectoryCatalog::codesMatching(DirectoryCatalog::languages(), $word);
            $organizations = DirectoryCatalog::codesMatching(
                collect(OrganizationType::cases())->mapWithKeys(fn (OrganizationType $type) => [$type->value => $type->label()])->all(),
                $word,
            );

            $query->where(function (Builder $query) use ($like, $countryCodes, $languageCodes, $organizations): void {
                $query->where('users.first_name', 'like', $like)
                    ->orWhere('users.last_name', 'like', $like)
                    ->orWhereIn('member_profiles.country_code', $countryCodes)
                    ->orWhereIn('member_profiles.organization_type', $organizations)
                    ->orWhereExists(fn (QueryBuilder $sub) => $sub->from('expertise_member_profile')
                        ->join('expertises', 'expertises.id', '=', 'expertise_member_profile.expertise_id')
                        ->whereColumn('expertise_member_profile.member_profile_id', 'member_profiles.id')
                        ->where('expertises.name', 'like', $like))
                    ->orWhereExists(fn (QueryBuilder $sub) => $sub->from('member_languages')
                        ->whereColumn('member_languages.member_profile_id', 'member_profiles.id')
                        ->whereIn('member_languages.language_code', $languageCodes));
            });
        }
    }

    /**
     * Valeurs cochées d'un même filtre : l'une ou l'autre ; filtres différents : tous à la fois.
     *
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['expertise'])) {
            $query->whereExists(fn (QueryBuilder $sub) => $sub->from('expertise_member_profile')
                ->whereColumn('expertise_member_profile.member_profile_id', 'member_profiles.id')
                ->whereIn('expertise_member_profile.expertise_id', $filters['expertise']));
        }

        if (! empty($filters['region'])) {
            $query->whereIn('member_profiles.country_code', DirectoryCatalog::countryCodesIn($filters['region']));
        }

        if (! empty($filters['language'])) {
            $query->whereExists(fn (QueryBuilder $sub) => $sub->from('member_languages')
                ->whereColumn('member_languages.member_profile_id', 'member_profiles.id')
                ->whereIn('member_languages.language_code', $filters['language']));
        }

        if (! empty($filters['organization'])) {
            $query->whereIn('member_profiles.organization_type', $filters['organization']);
        }

        if (! empty($filters['available'])) {
            $query->where('member_profiles.is_available', true);
        }
    }

    /**
     * Par nom (par défaut) ou par pays, dans l'ordre alphabétique des noms de pays en français.
     *
     * @param  Builder<User>  $query
     */
    private function applySort(Builder $query, ?string $sort): void
    {
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
