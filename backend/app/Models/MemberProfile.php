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
#[Fillable(['country_code', 'organization_type', 'is_available'])]
class MemberProfile extends Model
{
    /** @use HasFactory<MemberProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'organization_type' => OrganizationType::class,
            'is_available' => 'boolean',
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
     * @return HasMany<MemberLanguage, $this>
     */
    public function languages(): HasMany
    {
        return $this->hasMany(MemberLanguage::class)->orderBy('position');
    }

    /**
     * Remplace les langues de la médiatrice, dans l'ordre donné (codes ISO 639-1).
     * Une seule insertion, plutôt qu'une requête par langue.
     *
     * @param  list<string>  $codes
     */
    public function syncLanguages(array $codes): void
    {
        $this->getConnection()->transaction(function () use ($codes): void {
            $this->languages()->delete();

            MemberLanguage::query()->insert(array_map(
                fn (string $code, int $position): array => [
                    'member_profile_id' => $this->id,
                    'language_code' => $code,
                    'position' => $position,
                ],
                array_values($codes),
                array_keys(array_values($codes)),
            ));
        });

        $this->unsetRelation('languages');
    }
}
