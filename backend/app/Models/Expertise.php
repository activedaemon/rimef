<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ExpertiseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Domaine d'expertise (liste de référence, ExpertiseSeeder).
 */
#[Fillable(['name'])]
class Expertise extends Model
{
    /** @use HasFactory<ExpertiseFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<MemberProfile, $this>
     */
    public function memberProfiles(): BelongsToMany
    {
        return $this->belongsToMany(MemberProfile::class);
    }
}
