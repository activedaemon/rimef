<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * Email d'invitation envoyé depuis la rubrique Administration.
 *
 * Le lien ouvre l'écran d'activation de la SPA sur le domaine du tenant
 * (ex. http://rimef.localhost:9280/activer-mon-compte?token=…&email=…), où l'invitée
 * choisit son mot de passe.
 */
class InvitationNotification extends Notification
{
    use Queueable;

    /**
     * Chemin de l'écran « Activer mon compte » dans frontend/app.
     */
    public const SPA_PATH = '/activer-mon-compte';

    public function __construct(
        #[\SensitiveParameter] public readonly string $token,
        public readonly string $inviterName,
        public readonly Carbon $expiresAt,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(self::SPA_PATH).'?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->email,
        ]);
        $expires = $this->expiresAt->copy()->locale('fr')->isoFormat('D MMMM YYYY');

        return (new MailMessage)
            ->subject('Votre invitation à rejoindre le réseau RIMeF')
            ->line("{$this->inviterName} vous invite à rejoindre l’espace des membres du Réseau International des Médiatrices Francophones.")
            ->line('Choisissez votre mot de passe pour activer votre compte. Vous pourrez ensuite compléter votre fiche de médiatrice.')
            ->action('Activer mon compte', $url)
            ->line("Ce lien est valable jusqu’au {$expires}. Passé ce délai, demandez une nouvelle invitation à l’équipe du réseau.")
            ->line('Si vous ne vous attendiez pas à cette invitation, ignorez cet email : aucun compte ne sera activé.');
    }
}
