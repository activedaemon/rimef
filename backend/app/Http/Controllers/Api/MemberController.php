<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListMembersRequest;
use App\Http\Resources\MemberResource;
use App\Services\MemberDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Annuaire du réseau (écran Réseau).
 */
class MemberController extends Controller
{
    public function __construct(private readonly MemberDirectory $directory) {}

    /**
     * Médiatrices filtrées et triées, par pages de 12 ; meta.directory_total = toutes les médiatrices.
     */
    public function index(ListMembersRequest $request): AnonymousResourceCollection
    {
        return MemberResource::collection($this->directory->search($request->validated()))
            ->additional(['meta' => ['directory_total' => $this->directory->total()]]);
    }

    /**
     * Valeurs des filtres (expertise, région, langue, organisation) avec leurs effectifs.
     */
    public function filters(): JsonResponse
    {
        return response()->json(['data' => $this->directory->facets()]);
    }
}
