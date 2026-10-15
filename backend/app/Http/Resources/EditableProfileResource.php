<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Expertise;
use App\Models\MemberZone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Valeurs actuelles de la page « Modifier mon profil » (GET et PUT /api/profile). `has_sheet` est faux pour
 * un compte d'administration sans fiche de médiatrice : la page se limite alors au prénom et au nom.
 * Fiche, expertises et zones chargées au préalable ; `has_sheet` posé par le contrôleur.
 *
 * @mixin User
 */
class EditableProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->memberProfile;

        return [
            'has_sheet' => (bool) $this->has_sheet,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'photo_url' => $this->photoUrl(),
            'job_title' => $profile?->job_title,
            'organization_type' => $profile?->organization_type?->value,
            'organization_name' => $profile?->organization_name,
            'country_code' => $profile?->country_code,
            'city' => $profile?->city,
            'tagline' => $profile?->tagline,
            'bio' => $profile?->bio,
            'experience_since' => $profile?->experience_since,
            'audiences' => $profile?->audiences,
            'is_available' => (bool) $profile?->is_available,
            'expertises' => $profile === null ? [] : $profile->expertises
                ->map(fn (Expertise $expertise): string => $expertise->name)->values()->all(),
            'zones' => $profile === null ? [] : $profile->zones
                ->map(fn (MemberZone $zone): string => $zone->country_code)->values()->all(),
            'links' => [
                'linkedin' => $profile?->links['linkedin'] ?? null,
                'website' => $profile?->links['website'] ?? null,
                'organization' => $profile?->links['organization'] ?? null,
                'other' => [
                    'label' => $profile?->links['other']['label'] ?? null,
                    'url' => $profile?->links['other']['url'] ?? null,
                ],
            ],
            'updated_at' => ($profile?->updated_at ?? $this->updated_at)?->toIso8601String(),
        ];
    }
}
