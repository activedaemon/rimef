<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventParticipationRequest;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * « J'y participe » : la personne connectée s'inscrit à un événement (avec ses jours de présence,
 * sa note et sa proposition de se retrouver sur place), les modifie, ou se désinscrit (idempotent).
 */
class EventParticipationController extends Controller
{
    public function store(StoreEventParticipationRequest $request, Event $event): Response
    {
        abort_if($event->isPast(), 422, 'Cet événement est terminé.');

        $userId = $request->user()->getKey();
        $attendance = $request->attendance($event);

        if ($event->participants()->whereKey($userId)->exists()) {
            if ($attendance !== []) {
                $event->participants()->updateExistingPivot($userId, $attendance);
            }
        } else {
            $event->participants()->attach($userId, $attendance);
        }

        return response()->noContent();
    }

    public function destroy(Request $request, Event $event): Response
    {
        $event->participants()->detach($request->user()->getKey());

        return response()->noContent();
    }
}
