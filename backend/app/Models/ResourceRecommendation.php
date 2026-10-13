<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avis d'une médiatrice sur une ressource : une phrase, affichée dans le bandeau « Lues et
 * partagées par les médiatrices » de la bibliothèque.
 */
#[Fillable(['resource_id', 'user_id', 'quote'])]
class ResourceRecommendation extends Model
{
    /**
     * @return BelongsTo<\App\Models\Resource, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * Médiatrice qui la recommande.
     *
     * @return BelongsTo<User, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
