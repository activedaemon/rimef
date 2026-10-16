<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvitationRequest;
use App\Http\Resources\AccountResource;
use App\Models\User;
use App\Services\Invitations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Invitations de la rubrique Administration : envoi, renvoi, annulation.
 */
class InvitationController extends Controller
{
    public function __construct(private readonly Invitations $invitations) {}

    public function store(StoreInvitationRequest $request): JsonResponse
    {
        /** @var User $inviter */
        $inviter = $request->user();
        $account = $this->invitations->invite($request->safe()->only(['first_name', 'last_name', 'email']), $request->invitedRoles(), $inviter);

        return (new AccountResource($account->load(['roles', 'memberProfile', 'invitedBy'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Renvoie l'invitation : nouveau lien, nouvelle échéance (aussi pour une invitation expirée),
     * au plus tôt Invitations::RESEND_DELAY_MINUTES après le dernier envoi.
     */
    public function resend(Request $request, User $user): AccountResource
    {
        Gate::authorize('manageInvitation', $user);
        abort_if(
            $this->invitations->sentTooRecently($user),
            422,
            'Cette invitation vient d’être envoyée. Vous pourrez la renvoyer '.Invitations::RESEND_DELAY_MINUTES.' minutes après le dernier envoi.',
        );

        /** @var User $inviter */
        $inviter = $request->user();
        $this->invitations->send($user, $inviter);

        return new AccountResource($user->load(['roles', 'memberProfile', 'invitedBy']));
    }

    public function destroy(User $user): Response
    {
        Gate::authorize('manageInvitation', $user);

        $this->invitations->cancel($user);

        return response()->noContent();
    }
}
