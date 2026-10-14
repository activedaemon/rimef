<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\EventRole;
use App\Enums\ResourceMemberRole;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\MemberZone;
use App\Models\Resource;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fiche d'une médiatrice (écran Profil médiatrice). Sans profil rempli, seuls l'identité et le nom sont renseignés.
 * Profil, expertises et zones chargés au préalable ; `is_favorite`, `conversation` et
 * `upcoming_events` et `contributions` posés par le contrôleur.
 *
 * @mixin User
 */
class MemberProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->memberProfile;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'photo_url' => $this->photoUrl(),
            'country' => $profile?->country_code === null ? null : [
                'code' => $profile->country_code,
                'name' => DirectoryCatalog::countryName($profile->country_code),
            ],
            'city' => $profile?->city,
            'organization' => $profile?->organizationLabel(),
            'job_title' => $profile?->job_title,
            'tagline' => $profile?->tagline,
            'bio' => $profile?->bio,
            'years_of_experience' => $profile?->yearsOfExperience(),
            'audiences' => $profile?->audiences,
            'is_available' => (bool) $profile?->is_available,
            'links' => $profile?->displayedLinks() ?? [],
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            // Conversation avec la personne connectée (onglet Messages), null sans échange
            'conversation' => $this->conversation ?? null,
            // Prochains événements où elle est inscrite, avec son rôle (posés par le contrôleur)
            'upcoming_events' => collect($this->upcoming_events ?? [])->map(fn (Event $event): array => [
                'id' => $event->id,
                'slug' => $event->slug,
                'title' => $event->title,
                'summary' => $event->summary,
                'type' => $event->type === null ? null : ['value' => $event->type->value, 'label' => $event->type->label()],
                'starts_at' => $event->starts_at->toIso8601String(),
                'ends_at' => $event->ends_at?->toIso8601String(),
                'place' => $event->placeLabel(),
                'is_online' => $event->is_online,
                'role' => $this->role($event),
            ])->values()->all(),
            // Ressources qu'elle a écrites ou auxquelles elle est liée (posées par le contrôleur)
            'resources' => collect($this->contributions ?? [])->map(fn (Resource $resource): array => [
                'id' => $resource->id,
                'slug' => $resource->slug,
                'title' => $resource->title,
                'type' => [
                    'value' => $resource->type->value,
                    'label' => $resource->type->label(),
                    'action' => $resource->type->action(),
                ],
                'source' => $resource->source,
                'published_on' => $resource->published_on->toDateString(),
                'duration_minutes' => $resource->duration_minutes,
                'role' => $this->contributionRole($resource),
                'event' => $resource->event === null ? null : [
                    'slug' => $resource->event->slug,
                    'title' => $resource->event->title,
                ],
            ])->values()->all(),
            'expertises' => $profile === null ? [] : $profile->expertises
                ->map(fn (Expertise $expertise): string => $expertise->name)->values()->all(),
            'zones' => $profile === null ? [] : $profile->zones
                ->map(fn (MemberZone $zone): array => [
                    'code' => $zone->country_code,
                    'name' => DirectoryCatalog::countryName($zone->country_code),
                ])->values()->all(),
        ];
    }

    /**
     * @return array{value: string, label: string}
     */
    private function role(Event $event): array
    {
        $role = EventRole::tryFrom((string) $event->pivot?->getAttribute('role')) ?? EventRole::Participant;

        return ['value' => $role->value, 'label' => $role->label()];
    }

    /**
     * Lien de la médiatrice avec une de ses contributions : autrice, ou son rôle de contributrice
     * ou de médiatrice citée.
     *
     * @return array{value: string, label: string}
     */
    private function contributionRole(Resource $resource): array
    {
        if ($resource->author_id === $this->id) {
            return ['value' => 'author', 'label' => 'Autrice'];
        }

        $role = $resource->members->first()?->pivot->getAttribute('role') ?? ResourceMemberRole::Contributor;

        return ['value' => $role->value, 'label' => $role->label()];
    }
}
