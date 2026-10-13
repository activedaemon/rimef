<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Region;
use App\Enums\ResourceType;
use App\Enums\Role;
use App\Models\Expertise;
use App\Models\Resource;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

/**
 * Bibliothèque du réseau (écran Ressources) : ressources cherchées, filtrées et triées, et
 * valeurs des filtres avec leurs effectifs.
 */
class ResourceDirectory
{
    public const PER_PAGE = 12;

    /** Tailles de page au choix, comme l'annuaire (grille de 4 colonnes). */
    public const PER_PAGE_OPTIONS = [12, 24, 48];

    public const SORTS = ['recent', 'oldest', 'title'];

    /** Filtre Date : « Moins de 6 mois », ou une année de publication (« 2025 »). */
    public const RECENT = 'recent';

    public const RECENT_MONTHS = 6;

    /**
     * @param  array{q?: string|null, type?: list<string>, theme?: list<int>, region?: list<string>, language?: list<string>, date?: string|null, linked?: bool, by_member?: bool, with_event?: bool, saved?: bool, sort?: string|null, per_page?: int|string}  $filters
     * @return LengthAwarePaginator<int, \App\Models\Resource>
     */
    public function search(array $filters, User $viewer): LengthAwarePaginator
    {
        $query = Resource::query()
            ->with(['themes', 'author.memberProfile'])
            ->withExists(['savers as is_saved' => fn (Builder $query) => $query->whereKey($viewer->getKey())]);

        $this->applySearch($query, (string) ($filters['q'] ?? ''));
        $this->applyFilters($query, $filters, $viewer);

        match ($filters['sort'] ?? 'recent') {
            'oldest' => $query->orderBy('published_on'),
            'title' => $query->orderBy('title'),
            default => $query->orderByDesc('published_on'),
        };
        $query->orderBy('id');

        return $query->paginate((int) ($filters['per_page'] ?? self::PER_PAGE))->withQueryString();
    }

    /**
     * Valeurs des filtres avec leur nombre de ressources, et taille de la bibliothèque.
     *
     * @return array{type: list<array{value: string, label: string, count: int}>, theme: list<array{value: int, label: string, count: int}>, region: list<array{value: string, label: string, count: int}>, language: list<array{value: string, label: string, count: int}>, date: list<array{value: string, label: string, count: int}>, total: int}
     */
    public function facets(): array
    {
        $resources = Resource::query()->get(['id', 'type', 'region', 'languages', 'published_on']);

        $types = $resources->countBy(fn (Resource $resource): string => $resource->type->value);
        $regions = $resources->countBy(fn (Resource $resource): string => $resource->region?->value ?? Resource::WORLD_REGION);
        $languages = $resources->flatMap(fn (Resource $resource): array => $resource->languages)
            ->filter(fn (string $code): bool => isset(Resource::LANGUAGES[$code]))
            ->countBy();
        $years = $resources->countBy(fn (Resource $resource): string => (string) $resource->published_on->year)->sortKeysDesc();
        $recent = $resources->filter(fn (Resource $resource): bool => $resource->published_on >= $this->recentSince())->count();

        $themes = Expertise::query()
            ->withCount('resources')
            ->get()
            ->filter(fn (Expertise $theme): bool => $theme->resources_count > 0)
            ->sortBy(fn (Expertise $theme): string => Str::ascii($theme->name));

        return [
            'type' => $this->options($types->all(), fn (string $value): string => ResourceType::from($value)->label()),
            'theme' => $themes->map(fn (Expertise $theme): array => [
                'value' => $theme->id,
                'label' => $theme->name,
                'count' => $theme->resources_count,
            ])->values()->all(),
            'region' => $this->options($regions->all(), fn (string $value): string => $value === Resource::WORLD_REGION
                ? 'Monde'
                : Region::from($value)->label()),
            'language' => $this->options($languages->all(), fn (string $code): string => Resource::LANGUAGES[$code]),
            'date' => [
                ...($recent > 0 ? [['value' => self::RECENT, 'label' => 'Moins de '.self::RECENT_MONTHS.' mois', 'count' => $recent]] : []),
                ...$years->map(fn (int $count, string $year): array => [
                    'value' => $year,
                    'label' => "Publiées en {$year}",
                    'count' => $count,
                ])->values()->all(),
            ],
            'total' => $resources->count(),
        ];
    }

    /**
     * Chaque mot doit apparaître dans le titre, la source, le type, la région, une thématique,
     * ou le nom de l'autrice ou d'une médiatrice liée.
     *
     * @param  Builder<\App\Models\Resource>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $words = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';
            $types = DirectoryCatalog::codesMatching($this->labels(ResourceType::cases()), $word);
            $regions = DirectoryCatalog::codesMatching($this->labels(Region::cases()), $word);
            $names = fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('users.first_name', 'like', $like)
                ->orWhere('users.last_name', 'like', $like));

            $query->where(function (Builder $query) use ($like, $types, $regions, $names): void {
                $query->where('resources.title', 'like', $like)
                    ->orWhere('resources.source', 'like', $like)
                    ->orWhereIn('resources.type', $types)
                    ->orWhereIn('resources.region', $regions)
                    ->orWhereHas('themes', fn (Builder $query) => $query->where('expertises.name', 'like', $like))
                    ->orWhereHas('author', $names)
                    ->orWhereHas('members', fn (Builder $query) => $names($this->listed($query)));
            });
        }
    }

    /**
     * Valeurs cochées d'un même filtre : l'une ou l'autre ; filtres différents : tous à la fois.
     *
     * @param  Builder<\App\Models\Resource>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, User $viewer): void
    {
        if (! empty($filters['type'])) {
            $query->whereIn('resources.type', $filters['type']);
        }

        if (! empty($filters['theme'])) {
            $query->whereExists(fn (QueryBuilder $sub) => $sub->from('expertise_resource')
                ->whereColumn('expertise_resource.resource_id', 'resources.id')
                ->whereIn('expertise_resource.expertise_id', $filters['theme']));
        }

        if (! empty($filters['region'])) {
            $regions = array_values(array_diff($filters['region'], [Resource::WORLD_REGION]));
            $world = in_array(Resource::WORLD_REGION, $filters['region'], true);

            $query->where(fn (Builder $query) => $query
                ->whereIn('resources.region', $regions)
                ->when($world, fn (Builder $query) => $query->orWhereNull('resources.region')));
        }

        if (! empty($filters['language'])) {
            $query->where(function (Builder $query) use ($filters): void {
                foreach ($filters['language'] as $code) {
                    $query->orWhereJsonContains('resources.languages', $code);
                }
            });
        }

        if (! empty($filters['date'])) {
            $filters['date'] === self::RECENT
                ? $query->where('resources.published_on', '>=', $this->recentSince()->toDateString())
                : $query->whereYear('resources.published_on', (int) $filters['date']);
        }

        // Autrice, contributrice ou recommandation d'une médiatrice du réseau
        if (! empty($filters['linked'])) {
            $query->where(fn (Builder $query) => $query
                ->whereNotNull('resources.author_id')
                ->orWhereHas('members', fn (Builder $query) => $this->listed($query)));
        }

        if (! empty($filters['by_member'])) {
            $query->whereNotNull('resources.author_id');
        }

        if (! empty($filters['with_event'])) {
            $query->whereNotNull('resources.event_id');
        }

        if (! empty($filters['saved'])) {
            $query->whereHas('savers', fn (Builder $query) => $query->whereKey($viewer->getKey()));
        }
    }

    private function recentSince(): CarbonImmutable
    {
        return now()->toImmutable()->subMonths(self::RECENT_MONTHS)->startOfDay();
    }

    /**
     * Médiatrices de l'annuaire : comptes actifs ayant le rôle member.
     *
     * @template TQuery of Builder|BelongsToMany
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    private function listed(Builder|BelongsToMany $query): Builder|BelongsToMany
    {
        return $query->role(Role::Member->value)->where('users.is_active', true);
    }

    /**
     * @param  list<ResourceType|Region>  $cases
     * @return array<string, string> valeur => libellé
     */
    private function labels(array $cases): array
    {
        return collect($cases)->mapWithKeys(fn (ResourceType|Region $case): array => [$case->value => $case->label()])->all();
    }

    /**
     * @param  array<string, int>  $counts  valeur => nombre
     * @param  callable(string): string  $label
     * @return list<array{value: string, label: string, count: int}>
     */
    private function options(array $counts, callable $label): array
    {
        return collect($counts)
            ->map(fn (int $count, string $value): array => ['value' => $value, 'label' => $label($value), 'count' => $count])
            ->sortBy(fn (array $option): string => Str::ascii($option['label']))
            ->values()
            ->all();
    }
}
