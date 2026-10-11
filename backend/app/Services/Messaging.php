<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

/**
 * Messagerie interne : conversations par paire de membres, messages, lecture et notifications.
 *
 * Pour chaque destinataire : une seule entrée de cloche par conversation (remplacée à chaque
 * message, qu'elle ait été lue ou non) et un seul email tant que la conversation n'a pas été
 * ouverte.
 */
class Messaging
{
    /**
     * Conversation entre deux membres, créée au premier message.
     */
    public function conversationBetween(User $first, User $second): Conversation
    {
        $conversation = Conversation::query()->firstOrCreate(['pair_key' => Conversation::pairKey($first, $second)]);
        $conversation->participants()->syncWithoutDetaching([$first->getKey(), $second->getKey()]);

        return $conversation;
    }

    public function send(Conversation $conversation, User $sender, string $body): Message
    {
        $message = DB::transaction(function () use ($conversation, $sender, $body): Message {
            /** @var Message $message */
            $message = $conversation->messages()->create([
                'user_id' => $sender->getKey(),
                'body' => $body,
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);
            // Ses propres messages sont lus
            $conversation->participants()->updateExistingPivot($sender->getKey(), ['last_read_message_id' => $message->id]);

            return $message;
        });

        $this->notifyRecipients($conversation, $message->setRelation('sender', $sender));

        return $message;
    }

    /**
     * La conversation est ouverte : tout est lu, l'entrée de la cloche aussi, et le prochain
     * message pourra de nouveau déclencher un email.
     */
    public function markAsRead(Conversation $conversation, User $reader): void
    {
        $lastId = $conversation->messages()->max('id');

        $conversation->participants()->updateExistingPivot($reader->getKey(), [
            'last_read_message_id' => $lastId,
            'emailed_at' => null,
        ]);

        $this->bellEntries($reader, $conversation)->update(['read_at' => now()]);
    }

    /**
     * Messages reçus et non lus, toutes conversations confondues.
     */
    public function unreadCount(User $user): int
    {
        return Message::query()
            ->join('conversation_user', function (JoinClause $join) use ($user): void {
                $join->on('conversation_user.conversation_id', '=', 'messages.conversation_id')
                    ->where('conversation_user.user_id', $user->getKey());
            })
            ->where(fn (Builder $query) => $query->whereNull('messages.user_id')->orWhere('messages.user_id', '!=', $user->getKey()))
            ->whereRaw('messages.id > coalesce(conversation_user.last_read_message_id, 0)')
            ->count();
    }

    /**
     * Conversation engagée entre la personne connectée et une autre membre (onglet Messages
     * d'une fiche), avec ses messages non lus ; null si elles n'ont jamais échangé.
     *
     * @return array{id: int, unread_count: int}|null
     */
    public function summaryBetween(User $viewer, User $other): ?array
    {
        if ($viewer->is($other)) {
            return null;
        }

        $conversation = Conversation::query()
            ->where('pair_key', Conversation::pairKey($viewer, $other))
            ->whereHas('messages')
            ->first();

        if ($conversation === null) {
            return null;
        }

        $lastRead = (int) $conversation->participants()->whereKey($viewer->getKey())->value('last_read_message_id');

        return [
            'id' => $conversation->id,
            'unread_count' => $conversation->messages()
                ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', '!=', $viewer->getKey()))
                ->where('id', '>', $lastRead)
                ->count(),
        ];
    }

    private function notifyRecipients(Conversation $conversation, Message $message): void
    {
        $recipients = $conversation->participants()
            ->whereKeyNot($message->user_id)
            ->where('users.is_active', true)
            ->get();

        foreach ($recipients as $recipient) {
            $unread = $conversation->messages()
                ->where('user_id', '!=', $recipient->getKey())
                ->where('id', '>', (int) $recipient->pivot->last_read_message_id)
                ->count();
            $withEmail = $recipient->pivot->emailed_at === null;

            $this->bellEntries($recipient, $conversation)->delete();
            $recipient->notify(NewMessageNotification::for($message, $unread, $withEmail));

            if ($withEmail) {
                $conversation->participants()->updateExistingPivot($recipient->getKey(), ['emailed_at' => now()]);
            }
        }
    }

    /**
     * Entrées de la cloche « nouveau message » d'une conversation.
     *
     * @return MorphMany<DatabaseNotification, User>
     */
    private function bellEntries(User $user, Conversation $conversation): MorphMany
    {
        return $user->notifications()
            ->where('type', NewMessageNotification::class)
            ->where('data->conversation_id', $conversation->getKey());
    }
}
