<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\MemberDirectory;
use Illuminate\Http\Request;

/**
 * Membre connecté : la SPA l'appelle au démarrage pour savoir si une session est ouverte.
 * Sa photo (pastille du menu du compte) n'est donnée que si elle est servie, c'est-à-dire
 * pour un compte de l'annuaire.
 */
class CurrentUserController extends Controller
{
    public function __invoke(Request $request, MemberDirectory $directory): UserResource
    {
        $user = $request->user()->load('memberProfile');
        $user->setAttribute('photo_url', $directory->isListed($user) ? $user->photoUrl() : null);

        return new UserResource($user);
    }
}
