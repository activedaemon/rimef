<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\ContactSubject;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Nouveau message de la messagerie interne : entrée de la cloche (une par conversation non lue,
 * remplacée à chaque message, voir Messaging) et, au premier message non lu seulement, un email.
 *
 * La cloche est écrite tout de suite ; l'email part par la file d'attente.
 * Les adresses ne figurent jamais dans l'email : la réponse se fait sur RIMeF.
 */
class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $channels  « database » (cloche) et/ou « mail »
     */
    public function __construct(
        public readonly int $conversationId,
        public readonly string $senderName,
        public readonly ?string $senderPhotoUrl,
        public readonly ?ContactSubject $subject,
        public readonly string $excerpt,
        public readonly int $unreadCount,
        public readonly string $url,
        public readonly array $channels,
    ) {}

    /**
     * L'URL est calculée pendant la requête : le worker de la file ne connaît pas le domaine du tenant.
     */
    public static function for(Message $message, int $unreadCount, bool $withEmail): self
    {
        $sender = $message->sender;

        return new self(
            conversationId: $message->conversation_id,
            senderName: $sender->name ?? 'Une membre',
            senderPhotoUrl: $sender?->photoUrl(),
            subject: $message->subject,
            excerpt: Str::limit((string) preg_replace('/\s+/u', ' ', $message->body), 160),
            unreadCount: $unreadCount,
            url: url(self::path($message->conversation_id)),
            channels: $withEmail ? ['database', 'mail'] : ['database'],
        );
    }

    /**
     * Écran de la conversation dans la SPA.
     */
    public static function path(int $conversationId): string
    {
        return "/messages/{$conversationId}";
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * @return array<string, string>
     */
    public function viaConnections(): array
    {
        return ['database' => 'sync'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Nouveau message de {$this->senderName} sur RIMeF")
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("{$this->senderName} vous a écrit sur la messagerie du réseau.");

        if ($this->subject !== null) {
            $mail->line("**Objet :** {$this->subject->label()}");
        }

        return $mail
            ->line("> {$this->excerpt}")
            ->action('Lire et répondre', $this->url)
            ->line('Vous ne recevrez pas d’autre email pour cette conversation tant que vous ne l’aurez pas ouverte.');
    }

    /**
     * Entrée de la cloche.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'title' => $this->unreadCount > 1
                ? "{$this->unreadCount} nouveaux messages de {$this->senderName}"
                : "Nouveau message de {$this->senderName}",
            'text' => $this->excerpt,
            'path' => self::path($this->conversationId),
            'sender_name' => $this->senderName,
            'photo_url' => $this->senderPhotoUrl,
        ];
    }
}
