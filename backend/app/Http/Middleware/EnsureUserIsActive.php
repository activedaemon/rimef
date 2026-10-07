<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuse l'accès (403) à un compte désactivé, y compris avec une session déjà ouverte.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->is_active) {
            abort(Response::HTTP_FORBIDDEN, 'Votre compte est désactivé. Contactez une administratrice du réseau.');
        }

        return $next($request);
    }
}
