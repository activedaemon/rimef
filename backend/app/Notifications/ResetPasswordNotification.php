<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Email de réinitialisation du mot de passe, en français.
 *
 * Le lien ouvre l'écran de la SPA sur le domaine du tenant qui a reçu la demande
 * (ex. http://rimef.localhost:9280/reinitialiser-mot-de-passe?token=…&email=…).
 */
class ResetPasswordNotification extends ResetPassword
{
    /**
     * Chemin de l'écran « Choisir un nouveau mot de passe » dans frontend/app.
     */
    public const SPA_PATH = '/reinitialiser-mot-de-passe';

    /**
     * @param  mixed  $notifiable
     */
    protected function resetUrl($notifiable): string
    {
        return url(self::SPA_PATH).'?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    /**
     * @param  string  $url
     */
    protected function buildMailMessage($url): MailMessage
    {
        $expireMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe RIMeF')
            ->greeting('Bonjour,')
            ->line('Vous recevez cet email car une réinitialisation du mot de passe a été demandée pour votre compte.')
            ->action('Choisir un nouveau mot de passe', $url)
            ->line("Ce lien est valable {$expireMinutes} minutes.")
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez cet email : votre mot de passe reste inchangé.')
            ->salutation('L’équipe RIMeF');
    }
}
