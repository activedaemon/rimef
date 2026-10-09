<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HomeFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Accueil : tout son contenu en une seule requête (réseau mobile lent).
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, HomeFeed $feed): JsonResponse
    {
        return response()->json(['data' => $feed->for($request->user())]);
    }
}
