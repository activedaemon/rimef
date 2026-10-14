<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListResourcesRequest;
use App\Http\Resources\ResourceDetailResource;
use App\Http\Resources\ResourceRecommendationResource;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;
use App\Services\ResourceDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Bibliothèque du réseau (écran Ressources) et signets.
 */
class ResourceController extends Controller
{
    public function __construct(private readonly ResourceDirectory $directory) {}

    /**
     * Ressources cherchées, filtrées et triées, par pages de 9 (18 ou 36 au choix).
     */
    public function index(ListResourcesRequest $request): AnonymousResourceCollection
    {
        return ResourceResource::collection($this->directory->search($request->validated(), $request->user()));
    }

    /**
     * Valeurs des filtres avec leurs effectifs, et taille de la bibliothèque.
     */
    public function filters(): JsonResponse
    {
        return response()->json(['data' => $this->directory->facets()]);
    }

    /**
     * Derniers avis des médiatrices (bandeau « Lues et partagées par les médiatrices »).
     */
    public function voices(): AnonymousResourceCollection
    {
        return ResourceRecommendationResource::collection($this->directory->voices());
    }

    /**
     * Fiche d'une ressource, retrouvée par son slug (/ressources/women-mediators-and-inclusive-peace-processes).
     */
    public function show(Request $request, Resource $resource): ResourceDetailResource
    {
        $resource = $this->directory->detail($resource, $request->user());
        $resource->setRelation('related', $this->directory->related($resource));

        return new ResourceDetailResource($resource);
    }

    /**
     * Enregistre la ressource pour la personne connectée (idempotent).
     */
    public function save(Request $request, Resource $resource): Response
    {
        $request->user()->savedResources()->syncWithoutDetaching([$resource->getKey()]);

        return response()->noContent();
    }

    /**
     * Retire la ressource des ressources enregistrées (idempotent).
     */
    public function unsave(Request $request, Resource $resource): Response
    {
        $request->user()->savedResources()->detach($resource->getKey());

        return response()->noContent();
    }
}
