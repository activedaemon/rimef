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
    'country_code', 'city', 'organization_type', 'job_title', 'tagline', 'bio', 'experience_since',
    'audiences', 'is_available', 'photo_path',
])]
class MemberProfile extends Model
{
    /** @use HasFactory<MemberProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'organization_type' => OrganizationType::class,
            'is_available' => 'boolean',
            'experience_since' => 'integer',
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
     * Années d'expérience en médiation, d'après l'année de début ; null si elle n'est pas renseignée.
     */
    public function yearsOfExperience(): ?int
    {
        return $this->experience_since === null ? null : max(0, now()->year - $this->experience_since);
    }
}
