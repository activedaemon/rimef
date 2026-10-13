<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ResourceRecommendation;
use App\Support\DirectoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Avis d'une médiatrice : sa phrase, la ressource concernée et la médiatrice (photo, pays et
 * première expertise).
 *
 * @mixin ResourceRecommendation
 */
class ResourceRecommendationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->member->memberProfile;
        // $this->resource désigne l'avis lui-même (JsonResource) : la ressource est sa relation
        $resource = $this->resource->getRelationValue('resource');

        return [
            'id' => $this->id,
            'quote' => $this->quote,
            'resource' => [
                'id' => $resource->id,
                'slug' => $resource->slug,
                'title' => $resource->title,
                'url' => $resource->url,
            ],
            'member' => [
                'id' => $this->member->id,
                'slug' => $this->member->slug,
                'name' => $this->member->name,
                'photo_url' => $this->member->photoUrl(),
                'country' => DirectoryCatalog::countryName($profile?->country_code),
                'expertise' => $profile?->expertises->first()?->name,
            ],
        ];
    }
}
