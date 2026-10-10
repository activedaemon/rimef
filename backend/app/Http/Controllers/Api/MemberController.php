<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListMembersRequest;
use App\Http\Resources\MemberProfileResource;
use App\Http\Resources\MemberResource;
use App\Models\User;
use App\Services\MemberDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        return MemberResource::collection($this->directory->search($request->validated(), $request->user()))
            ->additional(['meta' => ['directory_total' => $this->directory->total()]]);
    }

    /**
     * Valeurs des filtres (expertise, région, organisation) avec leurs effectifs.
     */
    public function filters(): JsonResponse
    {
        return response()->json(['data' => $this->directory->facets()]);
    }

    /**
     * Fiche d'une médiatrice de l'annuaire, retrouvée par son slug (/reseau/aminata-diallo).
     */
    public function show(Request $request, User $user): MemberProfileResource
    {
        abort_unless($this->directory->isListed($user), 404);

        $user->load(['memberProfile.expertises', 'memberProfile.zones']);
        $user->setAttribute('is_favorite', $request->user()->favorites()->whereKey($user->getKey())->exists());

        return new MemberProfileResource($user);
    }

    /**
     * Photo d'une médiatrice de l'annuaire, réservée aux comptes connectés (fichier privé du tenant).
     */
    public function photo(User $user): StreamedResponse
    {
        $path = $this->directory->isListed($user) ? $user->memberProfile?->photo_path : null;

        abort_if($path === null || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }

    /**
     * Ajoute la médiatrice aux favoris de la personne connectée (idempotent).
     */
    public function favorite(Request $request, User $user): Response
    {
        abort_unless($this->directory->isListed($user), 404);

        $request->user()->favorites()->syncWithoutDetaching([$user->getKey()]);

        return response()->noContent();
    }

    /**
     * Retire la médiatrice des favoris de la personne connectée (idempotent).
     */
    public function unfavorite(Request $request, User $user): Response
    {
        $request->user()->favorites()->detach($user->getKey());

        return response()->noContent();
    }
}
