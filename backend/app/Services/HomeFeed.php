<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Contenu de l'accueil : événement à la une, prochains rendez-vous, dernières inscrites.
 */
class HomeFeed
{
    public const VISIBLE_ATTENDEES = 4;

    public const MEETINGS = 3;

    public const NEWS = 3;

    /**
     * @return array{featured_event: array<string, mixed>|null, meetings: list<array<string, mixed>>, news: list<array<string, mixed>>}
     */
    public function for(User $viewer): array
    {
        $featured = $this->upcoming()
            ->where('is_featured', true)
            ->withExists(['participants as is_participating' => fn (Builder $query) => $query->whereKey($viewer->getKey())])
            ->with(['participants' => fn (BelongsToMany $query) => $this->listed($query)
                ->leftJoin('member_profiles', 'member_profiles.user_id', '=', 'users.id')
                ->select('users.*')
                // Les participantes avec photo d'abord : la pile d'avatars de la maquette
                ->orderByRaw('member_profiles.photo_path IS NULL')
                ->orderBy('users.last_name')
                ->limit(self::VISIBLE_ATTENDEES)
                ->with('memberProfile')])
            ->first();

        $meetings = $this->upcoming()
            ->when($featured, fn (Builder $query) => $query->whereKeyNot($featured->getKey()))
            ->limit(self::MEETINGS)
            ->get();

        $news = $this->listed(User::query())
            ->with('memberProfile')
            ->latest()
            ->orderByDesc('users.id')
            ->limit(self::NEWS)
            ->get();

        return [
            'featured_event' => $featured === null ? null : [
                ...$this->event($featured),
                'description' => $featured->description,
                'is_participating' => (bool) $featured->is_participating,
                'attendees' => $featured->participants->map(fn (User $user): array => $this->person($user))->all(),
            ],
            'meetings' => $meetings->map(fn (Event $event): array => $this->event($event))->all(),
            'news' => $news->map(fn (User $user): array => [
                ...$this->person($user),
                'joined_at' => $user->created_at?->toIso8601String(),
            ])->all(),
        ];
    }

    /**
     * Événements à venir, du plus proche au plus lointain, avec leur nombre de participantes.
     *
     * @return Builder<Event>
     */
    private function upcoming(): Builder
    {
        return Event::query()
            ->where('starts_at', '>=', now())
            ->withCount(['participants as attendee_count' => fn (Builder $query) => $this->listed($query)])
            ->orderBy('starts_at')
            ->orderBy('id');
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
     * @return array{id: int, title: string, starts_at: string, ends_at: string|null, place: string|null, attendee_count: int}
     */
    private function event(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'starts_at' => $event->starts_at->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'place' => $event->place,
            'attendee_count' => (int) $event->attendee_count,
        ];
    }

    /**
     * @return array{id: int, name: string, photo_url: string|null}
     */
    private function person(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'photo_url' => $user->photoUrl()];
    }
}
