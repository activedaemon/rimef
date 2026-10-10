<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Expertise;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Carte d'une médiatrice dans l'annuaire. Sans profil rempli, seuls l'identité et le nom sont renseignés.
 *
 * @mixin User
 */
class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->memberProfile;
        $region = DirectoryCatalog::regionOf($profile?->country_code);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'country' => $profile?->country_code === null ? null : [
                'code' => $profile->country_code,
                'name' => DirectoryCatalog::countryName($profile->country_code),
            ],
            'region' => $region?->label(),
            'organization' => $profile?->organization_type?->label(),
            'is_available' => (bool) $profile?->is_available,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'next_event' => $this->nextEvent === null ? null : [
                'id' => $this->nextEvent->id,
                'title' => $this->nextEvent->title,
                'starts_at' => $this->nextEvent->starts_at->toIso8601String(),
            ],
            'photo_url' => $this->photoUrl(),
            'expertises' => $profile === null ? [] : $profile->expertises
                ->map(fn (Expertise $expertise): string => $expertise->name)->values()->all(),
        ];
    }
}
