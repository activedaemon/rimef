<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Event;
use App\Models\Expertise;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Événement de l'Agenda : date, lieu, type, thématiques et médiatrices qui y participent
 * (les premières, et leur nombre).
 *
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'type' => $this->type === null ? null : ['value' => $this->type->value, 'label' => $this->type->label()],
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'place' => $this->placeLabel(),
            'city' => $this->city,
            'country' => $this->country_code === null ? null : [
                'code' => $this->country_code,
                'name' => DirectoryCatalog::countryName($this->country_code),
            ],
            'is_online' => $this->is_online,
            'is_past' => $this->isPast(),
            'themes' => $this->themes->map(fn (Expertise $theme): string => $theme->name)->all(),
            'attendee_count' => (int) ($this->attendee_count ?? 0),
            'attendees' => $this->participants->map(fn (User $user): array => [
                'id' => $user->id,
                'slug' => $user->slug,
                'name' => $user->name,
                'photo_url' => $user->photoUrl(),
            ])->all(),
            'is_participating' => (bool) ($this->is_participating ?? false),
        ];
    }
}
