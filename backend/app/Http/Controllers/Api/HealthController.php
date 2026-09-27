<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * État de santé de l'API : confirme que Laravel répond et que la base est joignable.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $databaseUp = $this->isDatabaseUp();

        return response()->json([
            'status' => $databaseUp ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'database' => [
                'status' => $databaseUp ? 'ok' : 'down',
                'name' => DB::connection()->getDatabaseName(),
            ],
        ], $databaseUp ? 200 : 503);
    }

    private function isDatabaseUp(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
