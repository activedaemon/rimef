<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAccountsRequest;
use App\Http\Requests\Admin\UpdateAccountRolesRequest;
use App\Http\Resources\AccountResource;
use App\Models\User;
use App\Services\AccountDeletion;
use App\Services\AccountDirectory;
use App\Services\ProfileSummary;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Comptes du réseau, dans la rubrique Administration (/api/admin/accounts).
 */
class AccountController extends Controller
{
    public function __construct(
        private readonly AccountDirectory $directory,
        private readonly ProfileSummary $profiles,
    ) {}

    public function index(ListAccountsRequest $request): AnonymousResourceCollection
    {
        return AccountResource::collection($this->directory->search($request->validated()))
            ->additional(['meta' => ['facets' => $this->directory->facets()]]);
    }

    /**
     * Fiche d'un compte, avec l'état de sa fiche dans le réseau (carte « Fiche dans le réseau »).
     */
    public function show(User $user): AccountResource
    {
        $user->load([
            'roles',
            'memberProfile' => fn ($profile) => $profile->withCount(['expertises', 'zones']),
            'invitedBy',
        ]);
        $user->setAttribute('profile_summary', $this->profiles->for($user));

        return new AccountResource($user);
    }

    /**
     * Réactive le compte.
     */
    public function activate(User $user): AccountResource
    {
        Gate::authorize('toggleActivation', $user);

        $user->forceFill(['is_active' => true])->save();

        return $this->show($user);
    }

    /**
     * Désactive le compte : connexion refusée, sessions ouvertes fermées, absent de l'annuaire ;
     * conversations, participations et favoris conservés.
     */
    public function deactivate(User $user): AccountResource
    {
        Gate::authorize('toggleActivation', $user);

        DB::transaction(function () use ($user): void {
            $user->forceFill(['is_active' => false])->save();
            DB::table('sessions')->where('user_id', $user->getKey())->delete();
        });

        return $this->show($user);
    }

    public function updateRoles(UpdateAccountRolesRequest $request, User $user): AccountResource
    {
        Gate::authorize('updateRoles', $user);

        $user->syncRoles(array_map(fn (string $role): string => Role::from($role)->value, array_unique($request->validated('roles'))));

        return $this->show($user->refresh());
    }

    public function destroy(User $user, AccountDeletion $deletion): Response
    {
        Gate::authorize('delete', $user);

        $deletion->delete($user);

        return response()->noContent();
    }
}
