<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrganizationType;
use Database\Factories\MemberProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Profil de médiatrice affiché dans l'annuaire (un par compte ayant le rôle member).
 */
#[Fillable([
    'country_code', 'city', 'organization_type', 'organization_name', 'job_title', 'tagline', 'bio',
    'experience_since', 'audiences', 'links', 'is_available', 'photo_path',
])]
class MemberProfile extends Model
{
    /** @use HasFactory<MemberProfileFactory> */
    use HasFactory;

    /** Liens à intitulé fixe ; « other » porte son propre intitulé. */
    public const LINK_LABELS = [
        'linkedin' => 'LinkedIn',
        'website' => 'Site personnel',
        'organization' => 'Organisation',
    ];

    protected function casts(): array
    {
        return [
            'organization_type' => OrganizationType::class,
            'is_available' => 'boolean',
            'experience_since' => 'integer',
            'links' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Expertises dans l'ordre choisi par la médiatrice.
     *
     * @return BelongsToMany<Expertise, $this>
     */
    public function expertises(): BelongsToMany
    {
        return $this->belongsToMany(Expertise::class)
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /**
     * Zones d'intervention (pays) dans l'ordre choisi par la médiatrice.
     *
     * @return HasMany<MemberZone, $this>
     */
    public function zones(): HasMany
    {
        return $this->hasMany(MemberZone::class)->orderBy('position');
    }

    /**
     * Organisation affichée : son nom s'il est renseigné, sinon son type.
     */
    public function organizationLabel(): ?string
    {
        return $this->organization_name ?? $this->organization_type?->label();
    }

    /**
     * Liens de la fiche dans l'ordre d'affichage, avec leur intitulé.
     *
     * @return list<array{type: string, label: string, url: string}>
     */
    public function displayedLinks(): array
    {
        $links = $this->links ?? [];
        $displayed = [];

        foreach (self::LINK_LABELS as $type => $label) {
            if (isset($links[$type])) {
                $displayed[] = ['type' => $type, 'label' => $label, 'url' => $links[$type]];
            }
        }

        if (isset($links['other']['url'])) {
            $displayed[] = ['type' => 'other', 'label' => $links['other']['label'], 'url' => $links['other']['url']];
        }

        return $displayed;
    }

    /**
     * Années d'expérience en médiation, d'après l'année de début ; null si elle n'est pas renseignée.
     */
    public function yearsOfExperience(): ?int
    {
        return $this->experience_since === null ? null : max(0, now()->year - $this->experience_since);
    }
}
