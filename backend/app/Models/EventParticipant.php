<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Participation d'une médiatrice à un événement : rôle, jours de présence (null = tous les
 * jours), note facultative, proposition de se retrouver sur place.
 */
class EventParticipant extends Pivot
{
    protected $table = 'event_user';

    protected function casts(): array
    {
        return [
            'attendance_days' => 'array',
            'wants_to_meet' => 'boolean',
        ];
    }
}
