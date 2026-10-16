<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Invitations de la rubrique Administration (modèle Fruxa).
 *
 * Le compte est créé tout de suite, sans mot de passe et inactif (invitation_pending) : il
 * apparaît dans la liste avec le statut « Invitation en attente ». Le lien, signé par le
 * broker `invitations` (7 jours), mène à l'écran où l'invitée choisit son mot de passe.
 */
class Invitations
{
    /**
     * Délai minimal entre deux envois d'une même invitation : évite de relancer
     * plusieurs fois la personne invitée.
     */
    public const RESEND_DELAY_MINUTES = 10;

    /**
     * L'invitation a été envoyée il y a moins de RESEND_DELAY_MINUTES.
     */
    public function sentTooRecently(User $account): bool
    {
        return $account->invitationResendableAt()?->isFuture() === true;
    }

    /**
     * @param  array{first_name: string, last_name: string, email: string}  $identity
     * @param  list<Role>  $roles
     */
    public function invite(array $identity, array $roles, User $inviter): User
    {
        $account = DB::transaction(function () use ($identity, $roles, $inviter): User {
            $account = new User;
            $account->forceFill([
                'first_name' => trim($identity['first_name']),
                'last_name' => trim($identity['last_name']),
                'email' => Str::lower(trim($identity['email'])),
                'password' => null,
                'is_active' => false,
                'invitation_pending' => true,
                'invited_by_id' => $inviter->getKey(),
            ])->save();
            $account->syncRoles(array_map(fn (Role $role): string => $role->value, $roles));

            return $account;
        });

        $this->send($account, $inviter);

        return $account;
    }

    /**
     * Nouveau lien (l'ancien ne fonctionne plus) et nouvelle échéance.
     */
    public function send(User $account, User $inviter): void
    {
        $token = $this->broker()->createToken($account);

        $account->forceFill(['invited_at' => now(), 'invited_by_id' => $inviter->getKey()])->save();

        $account->notify(new InvitationNotification($token, $inviter->name, $account->invitationExpiresAt() ?? now()));
    }

    /**
     * Annule une invitation : le lien ne fonctionne plus et le compte disparaît.
     */
    public function cancel(User $account): void
    {
        DB::transaction(function () use ($account): void {
            $this->broker()->deleteToken($account);
            $account->delete();
        });
    }

    /**
     * Active le compte avec le mot de passe choisi. Renvoie le statut du broker
     * (Password::PASSWORD_RESET si le lien était valable).
     *
     * @param  array{email: string, token: string, password: string}  $credentials
     */
    public function accept(array $credentials): string
    {
        return $this->broker()->reset($credentials, function (User $account, string $password): void {
            $account->forceFill([
                'password' => $password,
                'invitation_pending' => false,
                'is_active' => true,
                'email_verified_at' => now(),
                'activated_at' => now(),
            ])->save();
        });
    }

    private function broker(): PasswordBroker
    {
        /** @var PasswordBroker */
        return Password::broker('invitations');
    }
}
