<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * « J'y participe » : la personne connectée s'inscrit à un événement ou se désinscrit (idempotent).
 */
class EventParticipationController extends Controller
{
    public function store(Request $request, Event $event): Response
    {
        $event->participants()->syncWithoutDetaching([$request->user()->getKey()]);

        return response()->noContent();
    }

    public function destroy(Request $request, Event $event): Response
    {
        $event->participants()->detach($request->user()->getKey());

        return response()->noContent();
    }
}
