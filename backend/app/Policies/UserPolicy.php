<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AccountStatus;
use App\Models\User;

/**
 * Droits sur un compte, dans la rubrique Administration.
 *
 * Décidés ici pour le superadmin aussi (Gate::before ne s'applique pas aux abilities
 * qui visent un compte) : son propre compte et les invitations ont leurs propres règles.
 * L'accès à la rubrique (Gate « administer ») est vérifié par les routes.
 */
class UserPolicy
{
    /**
     * Désactiver ou réactiver : une administratrice agit sur les membres, le superadmin
     * sur tous les comptes sauf le sien ; pas sur une invitation.
     */
    public function toggleActivation(User $actor, User $account): bool
    {
        if ($account->invitation_pending || $account->isSuperAdmin() || $account->is($actor)) {
            return false;
        }

        return $actor->isSuperAdmin() || ! $account->isAdmin();
    }

    /**
     * Modifier les rôles (Membre, Administratrice) : superadmin seul.
     */
    public function updateRoles(User $actor, User $account): bool
    {
        return $actor->isSuperAdmin() && ! $account->isSuperAdmin() && ! $account->invitation_pending;
    }

    /**
     * Supprimer définitivement : superadmin seul (demande RGPD, compte créé par erreur).
     */
    public function delete(User $actor, User $account): bool
    {
        return $this->updateRoles($actor, $account);
    }

    /**
     * Renvoyer ou annuler une invitation : une administratrice ne gère que celles des membres.
     */
    public function manageInvitation(User $actor, User $account): bool
    {
        return $account->invitation_pending && ($actor->isSuperAdmin() || ! $account->isAdmin());
    }

    /**
     * Écrire à un compte (messagerie) : tous les comptes actifs s'écrivent entre eux, membres,
     * administratrices et superadmin compris (règle du 16/10/2026). Un compte désactivé, une
     * invitation ou soi-même : non ; une conversation avec un tel compte passe en lecture seule.
     */
    public function sendMessage(User $actor, User $account): bool
    {
        return ! $account->is($actor) && $account->accountStatus() === AccountStatus::Active;
    }
}
