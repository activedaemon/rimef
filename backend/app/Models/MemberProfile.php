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

/**
 * Profil de médiatrice affiché dans l'annuaire (un par compte ayant le rôle member).
 */
#[Fillable(['country_code', 'organization_type', 'is_available', 'photo_path'])]
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
}
