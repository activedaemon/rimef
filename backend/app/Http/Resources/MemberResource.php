<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Expertise;
use App\Models\MemberLanguage;
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
            'name' => $this->name,
            'country' => $profile?->country_code === null ? null : [
                'code' => $profile->country_code,
                'name' => DirectoryCatalog::countryName($profile->country_code),
            ],
            'region' => $region?->label(),
            'organization' => $profile?->organization_type?->label(),
            'is_available' => (bool) $profile?->is_available,
            'expertises' => $profile === null ? [] : $profile->expertises
                ->map(fn (Expertise $expertise): string => $expertise->name)->values()->all(),
            'languages' => $profile === null ? [] : $profile->languages
                ->map(fn (MemberLanguage $language): array => [
                    'code' => $language->language_code,
                    'name' => DirectoryCatalog::languageName($language->language_code),
                ])->values()->all(),
        ];
    }
}
