<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Messaging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Cloche de l'en-tête : notifications de la personne connectée et compteurs de non-lus.
 */
class NotificationController extends Controller
{
    public const PER_PAGE = 15;

    /**
     * Notifications, la plus récente d'abord (title, text, path de la SPA, photo).
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->paginate(self::PER_PAGE);

        return response()->json([
            'data' => $notifications->getCollection()->map(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? '',
                'text' => $notification->data['text'] ?? null,
                'path' => $notification->data['path'] ?? null,
                'photo_url' => $notification->data['photo_url'] ?? null,
                'sender_name' => $notification->data['sender_name'] ?? null,
                'is_read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * Pastille de la cloche et compteur de « Mes messages ».
     */
    public function unreadCount(Request $request, Messaging $messaging): JsonResponse
    {
        return response()->json(['data' => [
            'notifications' => $request->user()->unreadNotifications()->count(),
            'messages' => $messaging->unreadCount($request->user()),
        ]]);
    }

    public function read(Request $request, string $notification): Response
    {
        $request->user()->notifications()->findOrFail($notification)->markAsRead();

        return response()->noContent();
    }

    public function readAll(Request $request): Response
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->noContent();
    }
}
