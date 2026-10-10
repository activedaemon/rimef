<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Expertise;
use App\Models\MemberZone;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fiche d'une médiatrice (écran Profil médiatrice). Sans profil rempli, seuls l'identité et le nom sont renseignés.
 * Profil, expertises et zones chargés au préalable ; `is_favorite` posé par le contrôleur.
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
            'region' => DirectoryCatalog::regionOf($profile?->country_code)?->label(),
            'organization' => $profile?->organization_type?->label(),
            'job_title' => $profile?->job_title,
            'tagline' => $profile?->tagline,
            'bio' => $profile?->bio,
            'years_of_experience' => $profile?->yearsOfExperience(),
            'audiences' => $profile?->audiences,
            'is_available' => (bool) $profile?->is_available,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
            'expertises' => $profile === null ? [] : $profile->expertises
                ->map(fn (Expertise $expertise): string => $expertise->name)->values()->all(),
            'zones' => $profile === null ? [] : $profile->zones
                ->map(fn (MemberZone $zone): array => [
                    'code' => $zone->country_code,
                    'name' => DirectoryCatalog::countryName($zone->country_code),
                ])->values()->all(),
        ];
    }
}
