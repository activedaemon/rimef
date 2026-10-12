<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventCalendarRequest;
use App\Http\Requests\EventFiltersRequest;
use App\Http\Requests\ListEventsRequest;
use App\Http\Resources\EventResource;
use App\Services\EventDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Agenda du réseau (écran Agenda).
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
}
