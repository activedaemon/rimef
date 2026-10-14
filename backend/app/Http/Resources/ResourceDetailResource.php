<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Resource;
use App\Models\ResourceRecommendation;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Illuminate\Http\Request;

/**
 * Fiche d'une ressource : la carte de la bibliothèque, plus son résumé, sa longueur, ses langues,
 * sa région, l'organisation éditrice, les médiatrices liées (autrice, contributrices, citées,
 * recommandations avec leur phrase), l'événement associé et les ressources de la même thématique
 * (relation « related »).
 *
 * @mixin Resource
 */
class ResourceDetailResource extends ResourceResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'summary' => $this->summary,
            'pages' => $this->pages,
            'languages' => collect($this->languages)
                ->map(fn (string $code): ?string => Resource::LANGUAGES[$code] ?? null)
                ->filter()
                ->values()
                ->all(),
            'region' => $this->region?->label() ?? 'Monde',
            'organization' => $this->source === null ? null : [
                'name' => $this->source,
                'detail' => $this->source_detail,
                'url' => $this->source_url,
            ],
            'mediators' => $this->mediators(),
            'event' => $this->event === null ? null : [
                'id' => $this->event->id,
                'slug' => $this->event->slug,
                'title' => $this->event->title,
                'starts_at' => $this->event->starts_at->toIso8601String(),
                'ends_at' => $this->event->ends_at?->toIso8601String(),
                'place' => $this->event->placeLabel(),
                'note' => $this->event_note,
                'attendee_count' => (int) ($this->event->attendee_count ?? 0),
                'attendees' => $this->event->participants->map(fn (User $user): array => [
                    'id' => $user->id,
                    'slug' => $user->slug,
                    'name' => $user->name,
                    'photo_url' => $user->photoUrl(),
                ])->all(),
            ],
            'related' => $this->relationLoaded('related')
                ? $this->getRelation('related')->map(fn (Resource $resource): array => [
                    'id' => $resource->id,
                    'slug' => $resource->slug,
                    'title' => $resource->title,
                    'type' => ['value' => $resource->type->value, 'label' => $resource->type->label()],
                    'source' => $resource->source ?? $resource->author?->name,
                    'published_on' => $resource->published_on->toDateString(),
                    'duration_minutes' => $resource->duration_minutes,
                ])->values()->all()
                : [],
        ];
    }

    /**
     * Une entrée par médiatrice du réseau : l'autrice d'abord, puis les contributrices et les
     * médiatrices citées, puis celles qui la recommandent ; une même médiatrice garde son rôle et
     * sa phrase de recommandation.
     *
     * @return list<array<string, mixed>>
     */
    private function mediators(): array
    {
        $entries = [];

        if ($this->author !== null) {
            $entries[$this->author->id] = $this->mediator($this->author, ['value' => 'author', 'label' => 'Autrice']);
        }

        foreach ($this->members as $member) {
            $role = $member->pivot->getAttribute('role');
            $entries[$member->id] ??= $this->mediator($member, ['value' => $role->value, 'label' => $role->label()]);
            $entries[$member->id]['note'] ??= $member->pivot->getAttribute('note');
        }

        /** @var ResourceRecommendation $recommendation */
        foreach ($this->recommendations as $recommendation) {
            $entries[$recommendation->user_id] ??= $this->mediator($recommendation->member, null);
            $entries[$recommendation->user_id]['quote'] = $recommendation->quote;
        }

        return array_values($entries);
    }

    /**
     * @param  array{value: string, label: string}|null  $role
     * @return array<string, mixed>
     */
    private function mediator(User $member, ?array $role): array
    {
        $profile = $member->memberProfile;

        return [
            'id' => $member->id,
            'slug' => $member->slug,
            'name' => $member->name,
            'first_name' => $member->first_name,
            'photo_url' => $member->photoUrl(),
            'country' => DirectoryCatalog::countryName($profile?->country_code),
            'expertise' => $profile?->expertises->first()?->name,
            'role' => $role,
            'note' => null,
            'quote' => null,
        ];
    }
}
