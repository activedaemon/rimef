<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ResourceMemberRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Lien d'une médiatrice avec une ressource : son rôle et la note qui l'explique.
 */
class ResourceMember extends Pivot
{
    protected $table = 'resource_member';

    protected function casts(): array
    {
        return [
            'role' => ResourceMemberRole::class,
        ];
    }
}
