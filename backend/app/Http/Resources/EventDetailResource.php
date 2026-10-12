<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\EventRole;
use App\Enums\Role;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Expertise;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Illuminate\Http\Request;

/**
 * Fiche d'un événement : l'événement de l'Agenda, plus sa photo, sa salle, son organisateur,
 * ses liens, toutes les médiatrices présentes (rôle, présence, pays, expertises, proposition de
 * se retrouver sur place ; wants_to_meet = celle de la personne connectée) et son
 * programme découpé en jours dans le fuseau de l'utilisatrice (paramètre « timezone »).
 *
 * @mixin Event
 */
class EventDetailResource extends EventResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $timezone = (string) ($request->query('timezone') ?: 'UTC');

        return [
            ...parent::toArray($request),
            'wants_to_meet' => (bool) ($this->wants_to_meet ?? false),
            'status' => $this->isPast() ? 'past' : ($this->isOngoing() ? 'ongoing' : 'upcoming'),
            'description' => $this->description,
            'venue' => $this->venue,
            'photo_url' => $this->photoUrl(),
            'organizer' => $this->organizer_name === null ? null : [
                'name' => $this->organizer_name,
                'detail' => $this->organizer_detail,
            ],
            'languages' => $this->languages,
            'links' => array_values($this->links ?? []),
            'attendees' => $this->participants->map(fn (User $user): array => $this->attendee($user))->all(),
            'program' => $this->sessions
                ->groupBy(fn (EventSession $session): string => $session->starts_at->setTimezone($timezone)->toDateString())
                ->map(fn ($sessions, string $date): array => [
                    'date' => $date,
                    'sessions' => $sessions->map(fn (EventSession $session): array => [
                        'id' => $session->id,
                        'time' => $session->starts_at->setTimezone($timezone)->format('H:i'),
                        'title' => $session->title,
                        'detail' => $session->detail,
                        'is_network' => $session->is_network,
                        'speaker' => $this->speaker($session->speaker),
                    ])->values()->all(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attendee(User $user): array
    {
        $profile = $user->memberProfile;
        $role = EventRole::tryFrom((string) $user->pivot?->getAttribute('role')) ?? EventRole::Participant;

        return [
            'id' => $user->id,
            'slug' => $user->slug,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'photo_url' => $user->photoUrl(),
            'country' => $profile?->country_code === null ? null : DirectoryCatalog::countryName($profile->country_code),
            'expertises' => $profile === null ? [] : $profile->expertises
                ->map(fn (Expertise $expertise): string => $expertise->name)->values()->all(),
            'role' => ['value' => $role->value, 'label' => $role->label()],
            'attendance_note' => $user->pivot?->getAttribute('attendance_note'),
            'wants_to_meet' => (bool) $user->pivot?->getAttribute('wants_to_meet'),
        ];
    }

    /**
     * Intervenante d'un créneau, si elle figure dans l'annuaire (compte actif de médiatrice).
     *
     * @return array{name: string, slug: string, photo_url: string|null}|null
     */
    private function speaker(?User $speaker): ?array
    {
        if ($speaker === null || ! $speaker->is_active || ! $speaker->hasRole(Role::Member->value)) {
            return null;
        }

        return ['name' => $speaker->name, 'slug' => $speaker->slug, 'photo_url' => $speaker->photoUrl()];
    }
}
