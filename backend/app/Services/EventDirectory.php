<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EventRole;
use App\Enums\EventType;
use App\Enums\Region;
use App\Enums\Role;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Agenda du réseau : événements à venir, passés ou ceux de la personne connectée, filtrés,
 * cherchés et triés ; valeurs des filtres et jours du mini-calendrier.
 *
 * Les mois et les jours sont lus dans le fuseau de l'utilisatrice ($timezone) ; un événement
 * sur plusieurs jours appartient à chaque jour (et mois) qu'il couvre.
 */
class EventDirectory
{
    public const PER_PAGE = 10;

    /** Tailles de page au choix (Agenda). */
    public const PER_PAGE_OPTIONS = [10, 20, 50];

    /** Avatars affichés sur chaque événement (« +X » pour les autres). */
    public const VISIBLE_ATTENDEES = 3;

    /** Critère « 5 médiatrices du réseau ou plus ». */
    public const CROWD = 5;

    public const PERIODS = ['upcoming', 'past', 'mine'];

    /** Prochains événements affichés sur la fiche d'une médiatrice. */
    public const MEMBER_UPCOMING = 3;

    /**
     * @param  array{period?: string, q?: string|null, type?: list<string>, region?: list<string>, theme?: list<int>, month?: string, day?: string, favorites?: bool, my_zones?: bool, my_themes?: bool, online?: bool, crowd?: bool, sort?: string|null, per_page?: int|string, timezone?: string}  $filters
     * @return LengthAwarePaginator<int, Event>
     */
    public function search(array $filters, User $viewer): LengthAwarePaginator
    {
        $period = $filters['period'] ?? 'upcoming';
        $timezone = $filters['timezone'] ?? 'UTC';

        $query = $this->inPeriod($period, $viewer)
            ->withCount(['participants as attendee_count' => fn (Builder $query) => $this->listed($query)])
            ->withExists(['participants as is_participating' => fn (Builder $query) => $query->whereKey($viewer->getKey())])
            ->with(['themes', 'participants' => fn (BelongsToMany $query) => $this->listed($query)
                ->leftJoin('member_profiles', 'member_profiles.user_id', '=', 'users.id')
                ->select('users.*')
                // Les participantes avec photo d'abord, comme sur l'accueil
                ->orderByRaw('member_profiles.photo_path IS NULL')
                ->orderBy('users.last_name')
                ->limit(self::VISIBLE_ATTENDEES)
                ->with('memberProfile')]);

        $this->applySearch($query, (string) ($filters['q'] ?? ''));
        $this->applyFilters($query, $filters, $viewer, $timezone);

        if (($filters['sort'] ?? null) === 'attendees') {
            $query->orderByDesc('attendee_count');
        }
        // Passés : les plus récents d'abord
        $query->orderBy('starts_at', $period === 'past' ? 'desc' : 'asc')->orderBy('id');

        return $query->paginate((int) ($filters['per_page'] ?? self::PER_PAGE))->withQueryString();
    }

    /**
     * Valeurs des filtres pour l'onglet $period (avec leur nombre d'événements), nombre
     * d'événements de chaque onglet, et critères personnels utilisables par $viewer (« Dans mes
     * zones d'intervention » et « Sur mes thématiques » demandent un profil qui les renseigne).
     *
     * @return array{type: list<array{value: string, label: string, count: int}>, region: list<array{value: string, label: string, count: int}>, theme: list<array{value: int, label: string, count: int}>, month: list<array{value: string, label: string, count: int}>, periods: array<string, int>, toggles: array{my_zones: bool, my_themes: bool}}
     */
    public function facets(string $period, User $viewer, string $timezone): array
    {
        $events = $this->inPeriod($period, $viewer)->get(['id', 'type', 'starts_at', 'country_code', 'is_online']);

        $types = $events->whereNotNull('type')->countBy(fn (Event $event): string => $event->type->value);
        $regions = $events->map(fn (Event $event): ?string => $event->is_online ? Event::ONLINE_REGION : $event->region()?->value)
            ->filter()
            ->countBy();
        $months = $events->countBy(fn (Event $event): string => $event->starts_at->setTimezone($timezone)->format('Y-m'))
            ->sortKeys(descending: $period === 'past');

        $themes = Expertise::query()
            ->withCount(['events' => fn (Builder $query) => $query->whereIn('events.id', $events->modelKeys())])
            ->get()
            ->filter(fn (Expertise $theme): bool => $theme->events_count > 0)
            ->sortBy(fn (Expertise $theme): string => Str::ascii($theme->name));

        return [
            'type' => $this->options($types->all(), fn (string $value): string => EventType::from($value)->label()),
            'region' => $this->options($regions->all(), fn (string $value): string => $value === Event::ONLINE_REGION
                ? 'En ligne'
                : Region::from($value)->label()),
            'theme' => $themes->map(fn (Expertise $theme): array => [
                'value' => $theme->id,
                'label' => $theme->name,
                'count' => $theme->events_count,
            ])->values()->all(),
            'month' => $months->map(fn (int $count, string $month): array => [
                'value' => $month,
                'label' => $this->monthLabel($month),
                'count' => $count,
            ])->values()->all(),
            'periods' => collect(self::PERIODS)
                ->mapWithKeys(fn (string $name): array => [$name => $this->inPeriod($name, $viewer)->count()])
                ->all(),
            'toggles' => [
                'my_zones' => $this->zonesOf($viewer)->exists(),
                'my_themes' => $this->expertisesOf($viewer)->exists(),
            ],
        ];
    }

    /**
     * Fiche d'un événement : toutes les médiatrices de l'annuaire qui y participent (intervenantes
     * d'abord), le programme avec ses intervenantes, et la participation de $viewer.
     */
    public function detail(Event $event, User $viewer): Event
    {
        $event->load([
            'themes',
            'sessions.speaker.memberProfile',
            'sessions.speaker.roles',
            'participants' => fn (BelongsToMany $query) => $this->listed($query)
                ->with('memberProfile.expertises')
                ->orderByRaw('event_user.role = ? desc', [EventRole::Speaker->value])
                ->orderBy('users.last_name')
                ->orderBy('users.first_name'),
        ]);

        $event->setAttribute('attendee_count', $event->participants->count());
        $participation = $event->participants()->whereKey($viewer->getKey())->first();
        $event->setAttribute('is_participating', $participation !== null);
        $event->setAttribute('wants_to_meet', (bool) $participation?->pivot->getAttribute('wants_to_meet'));
        $event->setAttribute('attendance', $participation === null ? null : [
            'days' => $participation->pivot->getAttribute('attendance_days'),
            'note' => $participation->pivot->getAttribute('attendance_note'),
        ]);

        return $event;
    }

    /**
     * Prochains événements d'une médiatrice (fiche de l'annuaire), du plus proche au plus lointain,
     * avec son rôle.
     *
     * @return Collection<int, Event>
     */
    public function upcomingFor(User $member, int $limit = self::MEMBER_UPCOMING): Collection
    {
        return $member->events()
            ->whereRaw('coalesce(ends_at, starts_at) >= ?', [now()])
            ->orderBy('starts_at')
            ->orderBy('events.id')
            ->limit($limit)
            ->get();
    }

    /**
     * Jours du mois (« 2026-11 ») qui ont au moins un événement, avec leurs titres (mini-calendrier).
     *
     * @return list<array{date: string, events: list<array{id: int, title: string}>}>
     */
    public function calendar(string $month, string $timezone): array
    {
        $start = CarbonImmutable::parse("{$month}-01", $timezone)->startOfMonth();
        $end = $start->endOfMonth();

        $days = [];
        $events = $this->overlapping(Event::query(), $start, $end)->orderBy('starts_at')->orderBy('id')->get();

        foreach ($events as $event) {
            $first = CarbonImmutable::instance($event->starts_at)->setTimezone($timezone)->startOfDay()->max($start);
            $last = CarbonImmutable::instance($event->ends_at ?? $event->starts_at)->setTimezone($timezone)->startOfDay()->min($end);

            for ($day = $first; $day <= $last; $day = $day->addDay()) {
                $days[$day->toDateString()][] = ['id' => $event->id, 'title' => $event->title];
            }
        }

        ksort($days);

        return collect($days)->map(fn (array $events, string $date): array => ['date' => $date, 'events' => $events])
            ->values()
            ->all();
    }

    /**
     * @return Builder<Event>
     */
    private function inPeriod(string $period, User $viewer): Builder
    {
        $query = Event::query();

        return match ($period) {
            'past' => $query->whereRaw('coalesce(ends_at, starts_at) < ?', [now()]),
            'mine' => $query->whereHas('participants', fn (Builder $query) => $query->whereKey($viewer->getKey())),
            default => $query->whereRaw('coalesce(ends_at, starts_at) >= ?', [now()]),
        };
    }

    /**
     * Événements qui couvrent au moins un instant entre $start et $end.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    private function overlapping(Builder $query, CarbonImmutable $start, CarbonImmutable $end): Builder
    {
        return $query->where('starts_at', '<=', $end->utc())
            ->whereRaw('coalesce(ends_at, starts_at) >= ?', [$start->utc()]);
    }

    /**
     * Chaque mot doit apparaître dans le titre, le lieu, le type, la région, une thématique
     * ou le nom d'une participante.
     *
     * @param  Builder<Event>  $query
     */
    private function applySearch(Builder $query, string $search): void
    {
        $words = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';
            $countryCodes = [
                ...DirectoryCatalog::codesMatching(DirectoryCatalog::countryNames(), $word),
                ...DirectoryCatalog::countryCodesIn(DirectoryCatalog::codesMatching($this->labels(Region::cases()), $word)),
            ];
            $types = DirectoryCatalog::codesMatching($this->labels(EventType::cases()), $word);
            $online = DirectoryCatalog::codesMatching(['online' => 'En ligne'], $word) !== [];

            $query->where(function (Builder $query) use ($like, $countryCodes, $types, $online): void {
                $query->where('events.title', 'like', $like)
                    ->orWhere('events.city', 'like', $like)
                    ->orWhereIn('events.country_code', $countryCodes)
                    ->orWhereIn('events.type', $types)
                    ->when($online, fn (Builder $query) => $query->orWhere('events.is_online', true))
                    ->orWhereHas('themes', fn (Builder $query) => $query->where('expertises.name', 'like', $like))
                    ->orWhereHas('participants', fn (Builder $query) => $this->listed($query)
                        ->where(fn (Builder $query) => $query->where('users.first_name', 'like', $like)
                            ->orWhere('users.last_name', 'like', $like)));
            });
        }
    }

    /**
     * Valeurs cochées d'un même filtre : l'une ou l'autre ; filtres différents : tous à la fois.
     *
     * @param  Builder<Event>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters, User $viewer, string $timezone): void
    {
        if (! empty($filters['type'])) {
            $query->whereIn('events.type', $filters['type']);
        }

        if (! empty($filters['region'])) {
            $regions = array_values(array_diff($filters['region'], [Event::ONLINE_REGION]));
            $online = in_array(Event::ONLINE_REGION, $filters['region'], true);

            $query->where(fn (Builder $query) => $query
                ->whereIn('events.country_code', DirectoryCatalog::countryCodesIn($regions))
                ->when($online, fn (Builder $query) => $query->orWhere('events.is_online', true)));
        }

        if (! empty($filters['theme'])) {
            $query->whereExists(fn (QueryBuilder $sub) => $sub->from('event_expertise')
                ->whereColumn('event_expertise.event_id', 'events.id')
                ->whereIn('event_expertise.expertise_id', $filters['theme']));
        }

        if (! empty($filters['month'])) {
            $start = CarbonImmutable::parse("{$filters['month']}-01", $timezone)->startOfMonth();
            $this->overlapping($query, $start, $start->endOfMonth());
        }

        if (! empty($filters['day'])) {
            $day = CarbonImmutable::parse($filters['day'], $timezone);
            $this->overlapping($query, $day->startOfDay(), $day->endOfDay());
        }

        // Une médiatrice des favoris de $viewer y participe
        if (! empty($filters['favorites'])) {
            $query->whereExists(fn (QueryBuilder $sub) => $sub->from('event_user')
                ->join('favorites', 'favorites.member_id', '=', 'event_user.user_id')
                ->whereColumn('event_user.event_id', 'events.id')
                ->where('favorites.user_id', $viewer->getKey()));
        }

        // Dans un pays des zones d'intervention de $viewer
        if (! empty($filters['my_zones'])) {
            $query->whereIn('events.country_code', $this->zonesOf($viewer)->select('member_zones.country_code'));
        }

        // Une thématique fait partie des expertises de $viewer
        if (! empty($filters['my_themes'])) {
            $query->whereExists(fn (QueryBuilder $sub) => $sub->from('event_expertise')
                ->whereColumn('event_expertise.event_id', 'events.id')
                ->whereIn('event_expertise.expertise_id', $this->expertisesOf($viewer)->select('expertise_member_profile.expertise_id')));
        }

        if (! empty($filters['online'])) {
            $query->where('events.is_online', true);
        }

        if (! empty($filters['crowd'])) {
            $query->whereHas('participants', fn (Builder $query) => $this->listed($query), '>=', self::CROWD);
        }
    }

    /**
     * Pays des zones d'intervention de $viewer (vide sans profil de médiatrice).
     */
    private function zonesOf(User $viewer): QueryBuilder
    {
        return DB::table('member_zones')
            ->join('member_profiles', 'member_profiles.id', '=', 'member_zones.member_profile_id')
            ->where('member_profiles.user_id', $viewer->getKey());
    }

    /**
     * Expertises de $viewer (vide sans profil de médiatrice).
     */
    private function expertisesOf(User $viewer): QueryBuilder
    {
        return DB::table('expertise_member_profile')
            ->join('member_profiles', 'member_profiles.id', '=', 'expertise_member_profile.member_profile_id')
            ->where('member_profiles.user_id', $viewer->getKey());
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
     * @param  list<EventType|Region>  $cases
     * @return array<string, string> valeur => libellé
     */
    private function labels(array $cases): array
    {
        return collect($cases)->mapWithKeys(fn (EventType|Region $case): array => [$case->value => $case->label()])->all();
    }

    /**
     * « 2026-11 » → « Novembre 2026 ».
     */
    private function monthLabel(string $month): string
    {
        return Str::ucfirst(CarbonImmutable::parse("{$month}-01")->locale('fr')->isoFormat('MMMM YYYY'));
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
