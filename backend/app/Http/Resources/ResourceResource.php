<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Expertise;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Carte de la bibliothèque : type et appel à l'action, titre, source ou autrice, date de
 * publication, durée, thématiques et signet de la personne connectée.
 *
 * @mixin Resource
 */
class ResourceResource extends JsonResource
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
            'type' => [
                'value' => $this->type->value,
                'label' => $this->type->label(),
                'action' => $this->type->action(),
            ],
            'source' => $this->source,
            'author' => $this->author === null ? null : [
                'id' => $this->author->id,
                'slug' => $this->author->slug,
                'name' => $this->author->name,
                'photo_url' => $this->author->photoUrl(),
            ],
            'published_on' => $this->published_on->toDateString(),
            'duration_minutes' => $this->duration_minutes,
            'url' => $this->url,
            'themes' => $this->themes->map(fn (Expertise $theme): array => [
                'id' => $theme->id,
                'name' => $theme->name,
            ])->all(),
            'is_saved' => (bool) ($this->is_saved ?? false),
        ];
    }
}
