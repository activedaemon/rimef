<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse as FortifyFailedResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demande de lien de réinitialisation pour une adresse inconnue : même réponse
 * qu'en cas de succès, pour ne pas révéler qui est membre du réseau.
 * Les autres échecs (demandes trop rapprochées) restent signalés.
 */
class PasswordResetLinkRequestResponse implements FailedPasswordResetLinkRequestResponse
{
    public function __construct(private readonly string $status) {}

    public function toResponse($request): Response
    {
        if ($this->status === Password::INVALID_USER) {
            return (new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT))->toResponse($request);
        }

        return (new FortifyFailedResponse($this->status))->toResponse($request);
    }
}
