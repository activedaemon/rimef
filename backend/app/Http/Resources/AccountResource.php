<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Services\ProfileSummary;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Compte vu depuis la rubrique Administration, avec les actions permises
 * au compte qui regarde (UserPolicy).
 *
 * @mixin User
 */
class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $status = $this->accountStatus();
        $roles = $this->getRoleNames()->values()->all();

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'name' => $this->name,
            'email' => $this->email,
            'photo_url' => $this->photoUrl(),
            'roles' => $roles,
            'status' => $status->value,
            // Fiche du réseau : médiatrice active seulement (l'annuaire n'affiche pas les autres)
            'profile_slug' => $status === AccountStatus::Active && in_array(Role::Member->value, $roles, true) ? $this->slug : null,
            'is_protected' => $this->isSuperAdmin(),
            'is_self' => $this->resource->is($viewer),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'member_since' => $this->memberSince()?->toIso8601String(),
            'invited_at' => $this->invitation_pending ? $this->invited_at?->toIso8601String() : null,
            'invitation_expires_at' => $this->invitationExpiresAt()?->toIso8601String(),
            // Renvoi possible à partir de cette heure (délai entre deux envois)
            'invitation_resendable_at' => $this->invitationResendableAt()?->toIso8601String(),
            'invited_by' => $this->invitation_pending ? $this->invitedBy?->name : null,
            // Complétude de la fiche (liste et fiche) ; null sans rôle Membre ou pour une invitation
            'profile_completion' => in_array(Role::Member->value, $roles, true) && ! $this->invitation_pending
                ? ProfileSummary::completion($this->memberProfile)
                : null,
            // Fiche d'un compte seulement (AccountController::show), null dans la liste
            'profile_summary' => $this->profile_summary ?? null,
            'can' => [
                'toggle_activation' => $viewer->can('toggleActivation', $this->resource),
                'update_roles' => $viewer->can('updateRoles', $this->resource),
                'delete' => $viewer->can('delete', $this->resource),
                'manage_invitation' => $viewer->can('manageInvitation', $this->resource),
                'send_message' => $viewer->can('sendMessage', $this->resource),
            ],
        ];
    }
}
