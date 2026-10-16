<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Messaging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Équipe RIMeF (section de la fenêtre « Nouveau message ») : administratrices et superadmin
 * actifs, hors la personne connectée, avec la conversation déjà engagée s'il y en a une.
 */
class TeamController extends Controller
{
    public function __invoke(Request $request, Messaging $messaging): JsonResponse
    {
        /** @var User $me */
        $me = $request->user();

        $team = User::query()
            ->role([Role::Admin->value, Role::SuperAdmin->value])
            ->where('users.is_active', true)
            ->where('users.invitation_pending', false)
            ->whereKeyNot($me->getKey())
            ->with(['roles', 'memberProfile'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return response()->json(['data' => $team->map(fn (User $user): array => [
            'id' => $user->id,
            'slug' => $user->slug,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'photo_url' => $user->photoUrl(),
            'roles' => $user->getRoleNames()->values()->all(),
            'has_profile' => $user->hasRole(Role::Member->value),
            'conversation_id' => $messaging->summaryBetween($me, $user)['id'] ?? null,
        ])->values()]);
    }
}
