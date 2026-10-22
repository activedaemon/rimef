<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Masque les outils de développement (404) hors local, sauf si RIMEF_DEV_TOOLS est activé.
 */
class EnsureDevToolsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app()->environment('local') || config('rimef.dev_tools'), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
