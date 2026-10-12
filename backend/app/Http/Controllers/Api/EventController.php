<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventCalendarRequest;
use App\Http\Requests\EventFiltersRequest;
use App\Http\Requests\ListEventsRequest;
use App\Http\Requests\ShowEventRequest;
use App\Http\Resources\EventDetailResource;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\EventDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Agenda du réseau (écran Agenda) et fiche d'un événement.
 */
class EventController extends Controller
{
    public function __construct(private readonly EventDirectory $directory) {}

    /**
     * Événements de l'onglet demandé, filtrés et triés, par pages de 10.
     */
    public function index(ListEventsRequest $request): AnonymousResourceCollection
    {
        return EventResource::collection($this->directory->search($request->validated(), $request->user()));
    }

    /**
     * Valeurs des filtres de l'onglet avec leurs effectifs, et nombre d'événements par onglet.
     */
    public function filters(EventFiltersRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->directory->facets(
            $request->validated('period') ?? 'upcoming',
            $request->user(),
            $request->validated('timezone') ?? 'UTC',
        )]);
    }

    /**
     * Jours du mois qui ont des événements (mini-calendrier).
     */
    public function calendar(EventCalendarRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->directory->calendar(
            $request->validated('month'),
            $request->validated('timezone') ?? 'UTC',
        )]);
    }

    /**
     * Fiche d'un événement, retrouvé par son slug (/agenda/paris-peace-forum-2026).
     */
    public function show(ShowEventRequest $request, Event $event): EventDetailResource
    {
        return new EventDetailResource($this->directory->detail($event, $request->user()));
    }

    /**
     * Photo de l'événement, depuis le stockage privé du tenant.
     */
    public function photo(Event $event): StreamedResponse
    {
        abort_if($event->photo_path === null || ! Storage::disk('local')->exists($event->photo_path), 404);

        return Storage::disk('local')->response($event->photo_path, null, [
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }
}
