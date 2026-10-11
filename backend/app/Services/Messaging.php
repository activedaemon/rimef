<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

/**
 * Messagerie interne : conversations par paire de membres, messages (modifiables 15 minutes,
 * supprimables), lecture et notifications. Un message supprimé ne compte plus comme non lu.
 * Une conversation peut être supprimée « pour moi » (cleared_message_id) ; les messages que
 * toutes les participantes ont supprimés sont effacés de la base, puis la conversation.
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
     * Modification par l'autrice (contrôles faits par l'appelant) ; l'entrée de la cloche de la
     * destinataire reprend le nouveau texte si le message n'est pas encore lu.
     */
    public function edit(Message $message, string $body): Message
    {
        $message->update(['body' => $body, 'edited_at' => now()]);
        $this->refreshBellEntries($message);

        return $message;
    }

    /**
     * Suppression par l'autrice : le message reste à sa place, texte effacé. La cloche de la
     * destinataire est recalculée (retirée s'il ne reste aucun message non lu).
     */
    public function delete(Message $message): Message
    {
        $message->update(['body' => '', 'deleted_at' => now()]);
        $this->refreshBellEntries($message);

        return $message;
    }

    /**
     * Supprime la conversation pour $user : ses messages actuels lui sont masqués, tout est lu,
     * son entrée de cloche disparaît. Les messages supprimés par toutes les participantes sont
     * effacés ; s'il n'en reste aucun, la conversation aussi.
     */
    public function clearFor(Conversation $conversation, User $user): void
    {
        DB::transaction(function () use ($conversation, $user): void {
            $lastId = $conversation->messages()->max('id');

            $conversation->participants()->updateExistingPivot($user->getKey(), [
                'cleared_message_id' => $lastId,
                'last_read_message_id' => $lastId,
                'emailed_at' => null,
            ]);
            $this->bellEntries($user, $conversation)->delete();

            // Une participante qui n'a rien supprimé (null) bloque l'effacement
            $clearedByAll = (int) DB::table('conversation_user')
                ->where('conversation_id', $conversation->getKey())
                ->min(DB::raw('coalesce(cleared_message_id, 0)'));

            $conversation->messages()->where('id', '<=', $clearedByAll)->delete();

            if ($conversation->messages()->doesntExist()) {
                $conversation->delete();
            }
        });
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
            ->whereNull('messages.deleted_at')
            ->whereRaw('messages.id > coalesce(conversation_user.last_read_message_id, 0)')
            ->whereRaw('messages.id > coalesce(conversation_user.cleared_message_id, 0)')
            ->count();
    }

    /**
     * Conversation engagée entre la personne connectée et une autre membre (onglet Messages
     * d'une fiche), avec ses messages non lus ; null si elles n'ont jamais échangé ou si elle
     * l'a supprimée sans nouveau message depuis.
     *
     * @return array{id: int, unread_count: int}|null
     */
    public function summaryBetween(User $viewer, User $other): ?array
    {
        if ($viewer->is($other)) {
            return null;
        }

        $conversation = $viewer->conversations()
            ->where('pair_key', Conversation::pairKey($viewer, $other))
            ->first();
        $cleared = (int) $conversation?->pivot->cleared_message_id;

        if ($conversation === null || $conversation->messages()->where('id', '>', $cleared)->doesntExist()) {
            return null;
        }

        $lastRead = (int) $conversation->pivot->last_read_message_id;

        return [
            'id' => $conversation->id,
            'unread_count' => $this->unreadMessages($conversation, $viewer, $lastRead)->count(),
        ];
    }

    private function notifyRecipients(Conversation $conversation, Message $message): void
    {
        $recipients = $conversation->participants()
            ->whereKeyNot($message->user_id)
            ->where('users.is_active', true)
            ->get();

        foreach ($recipients as $recipient) {
            $unread = $this->unreadMessages($conversation, $recipient, (int) $recipient->pivot->last_read_message_id)->count();
            $withEmail = $recipient->pivot->emailed_at === null;

            $this->bellEntries($recipient, $conversation)->delete();
            $recipient->notify(NewMessageNotification::for($message, $unread, $withEmail));

            if ($withEmail) {
                $conversation->participants()->updateExistingPivot($recipient->getKey(), ['emailed_at' => now()]);
            }
        }
    }

    /**
     * Après une modification ou une suppression : l'entrée non lue de la cloche de chaque
     * destinataire reprend le nombre et le dernier des messages encore non lus, ou disparaît
     * s'il n'en reste aucun. Ni nouvelle notification, ni email.
     */
    private function refreshBellEntries(Message $message): void
    {
        $conversation = $message->conversation;
        $recipients = $conversation->participants()->whereKeyNot($message->user_id)->get();

        foreach ($recipients as $recipient) {
            $entries = $this->bellEntries($recipient, $conversation)->whereNull('read_at')->get();
            if ($entries->isEmpty()) {
                continue;
            }

            $unread = $this->unreadMessages($conversation, $recipient, (int) $recipient->pivot->last_read_message_id);
            /** @var Message|null $latest */
            $latest = (clone $unread)->with('sender')->latest('id')->first();

            if ($latest === null) {
                $entries->each->delete();

                continue;
            }

            $data = NewMessageNotification::for($latest, $unread->count(), false)->toArray($recipient);
            $entries->each(fn (DatabaseNotification $entry) => $entry->forceFill(['data' => $data])->save());
        }
    }

    /**
     * Messages reçus par $user après son dernier lu, hors messages supprimés.
     *
     * @return HasMany<Message, Conversation>
     */
    private function unreadMessages(Conversation $conversation, User $user, int $lastReadId): HasMany
    {
        return $conversation->messages()
            ->where(fn (Builder $query) => $query->whereNull('user_id')->orWhere('user_id', '!=', $user->getKey()))
            ->whereNull('deleted_at')
            ->where('id', '>', $lastReadId);
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
