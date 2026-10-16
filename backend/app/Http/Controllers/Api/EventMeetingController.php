<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * « Proposer de se retrouver sur place » (fiche d'un événement) : la personne connectée se dit
 * disponible pour retrouver les autres médiatrices présentes, ou retire sa proposition
 * (idempotent). Se proposer inscrit aussi à l'événement ; se désinscrire retire la proposition.
 */
class EventMeetingController extends Controller
{
    public function store(Request $request, Event $event): Response
    {
        abort_unless($request->user()->isMember(), 403, User::MEMBERS_ONLY_MESSAGE);
        abort_if($event->isPast(), 422, 'Cet événement est terminé.');

        $userId = $request->user()->getKey();

        if ($event->participants()->whereKey($userId)->exists()) {
            $event->participants()->updateExistingPivot($userId, ['wants_to_meet' => true]);
        } else {
            $event->participants()->attach($userId, ['wants_to_meet' => true]);
        }

        return response()->noContent();
    }

    public function destroy(Request $request, Event $event): Response
    {
        $event->participants()->updateExistingPivot($request->user()->getKey(), ['wants_to_meet' => false]);

        return response()->noContent();
    }
}
