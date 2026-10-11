<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListConversationsRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Messaging;
use App\Support\DirectoryCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Messagerie interne (écrans Mes messages et Conversation). Seules les participantes
 * accèdent à une conversation : 404 pour les autres.
 */
class ConversationController extends Controller
{
    public const PER_PAGE = 20;

    public const MESSAGES_PER_PAGE = 30;

    public function __construct(private readonly Messaging $messaging) {}

    /**
     * Mes conversations, la plus récente d'abord, avec leurs messages non lus ; ?q= (recherche du
     * bandeau) et ?unread=1 (filtre « Non lues »). meta : nombre de conversations et de non lues.
     */
    public function index(ListConversationsRequest $request): AnonymousResourceCollection
    {
        $me = $request->user();

        $query = $me->conversations()
            ->with(['participants.memberProfile', 'participants.roles', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn (Builder $query) => $this->unread($query, $me)])
            ->orderByDesc('conversations.last_message_at')
            ->orderByDesc('conversations.id');

        $this->applySearch($query, $me, (string) $request->validated('q'));

        if ($request->boolean('unread')) {
            $query->whereHas('messages', fn (Builder $query) => $this->unread($query, $me));
        }

        return ConversationResource::collection($query->paginate(self::PER_PAGE)->withQueryString())
            ->additional(['meta' => [
                'total_conversations' => $me->conversations()->count(),
                'unread_conversations' => $me->conversations()
                    ->whereHas('messages', fn (Builder $query) => $this->unread($query, $me))
                    ->count(),
            ]]);
    }

    public function show(Request $request, int $conversation): ConversationResource
    {
        return new ConversationResource($this->find($request->user(), $conversation)
            ->load(['participants.memberProfile', 'participants.roles', 'latestMessage']));
    }

    /**
     * Messages, les plus récents d'abord, par pages de 30 ; ?before=<id> pour les précédents.
     */
    public function messages(Request $request, int $conversation): JsonResponse
    {
        $before = $request->integer('before');
        $messages = $this->find($request->user(), $conversation)->messages()
            ->when($before > 0, fn (Builder $query) => $query->where('id', '<', $before))
            ->orderByDesc('id')
            ->limit(self::MESSAGES_PER_PAGE + 1)
            ->get();

        return response()->json([
            'data' => MessageResource::collection($messages->take(self::MESSAGES_PER_PAGE))->resolve($request),
            'meta' => ['has_more' => $messages->count() > self::MESSAGES_PER_PAGE],
        ]);
    }

    /**
     * Conversation ouverte : tout est lu.
     */
    public function read(Request $request, int $conversation): Response
    {
        $this->messaging->markAsRead($this->find($request->user(), $conversation), $request->user());

        return response()->noContent();
    }

    public function store(SendMessageRequest $request, int $conversation): JsonResponse
    {
        $message = $this->messaging->send(
            $this->find($request->user(), $conversation),
            $request->user(),
            $request->validated('body'),
        );

        return (new MessageResource($message))->response()->setStatusCode(201);
    }

    /**
     * Messages reçus après le dernier lu (requête jointe à conversation_user de $me).
     *
     * @param  Builder<Message>  $query
     * @return Builder<Message>
     */
    private function unread(Builder $query, User $me): Builder
    {
        return $query
            ->where(fn (Builder $query) => $query->whereNull('messages.user_id')->orWhere('messages.user_id', '!=', $me->getKey()))
            ->whereRaw('messages.id > coalesce(conversation_user.last_read_message_id, 0)');
    }

    /**
     * Chaque mot doit apparaître dans le nom, la ville ou le pays de l'interlocutrice, ou dans
     * le texte d'un message.
     *
     * @param  BelongsToMany<Conversation, User>  $query
     */
    private function applySearch(BelongsToMany $query, User $me, string $search): void
    {
        $words = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            $like = '%'.addcslashes($word, '%_\\').'%';
            $countryCodes = DirectoryCatalog::codesMatching(DirectoryCatalog::countryNames(), $word);

            $query->where(fn (Builder $query) => $query
                ->whereHas('participants', fn (Builder $participant) => $participant
                    ->whereKeyNot($me->getKey())
                    ->where(fn (Builder $participant) => $participant
                        ->where('users.first_name', 'like', $like)
                        ->orWhere('users.last_name', 'like', $like)
                        ->orWhereHas('memberProfile', fn (Builder $profile) => $profile
                            ->where('city', 'like', $like)
                            ->orWhereIn('country_code', $countryCodes))))
                ->orWhereHas('messages', fn (Builder $message) => $message->where('body', 'like', $like)));
        }
    }

    private function find(User $user, int $id): Conversation
    {
        return $user->conversations()->findOrFail($id);
    }
}
