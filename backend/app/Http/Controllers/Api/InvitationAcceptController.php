<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Services\Invitations;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Activation d'un compte invité depuis le lien reçu par email (sans session).
 * L'invitée se connecte ensuite avec son email et le mot de passe choisi.
 */
class InvitationAcceptController extends Controller
{
    public function __invoke(AcceptInvitationRequest $request, Invitations $invitations): JsonResponse
    {
        $status = $invitations->accept($request->safe()->only(['email', 'token', 'password']));

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Ce lien d’activation n’est plus valable. Demandez une nouvelle invitation à l’équipe du réseau.',
            ]);
        }

        return response()->json(['message' => 'Votre compte est activé. Vous pouvez vous connecter.']);
    }
}
