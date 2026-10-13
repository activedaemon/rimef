<?php

namespace App\Models;

use App\Enums\Role;
use App\Exceptions\ProtectedSuperAdminException;
use App\Models\Concerns\HasUniqueSlug;
use App\Notifications\ResetPasswordNotification;
use BackedEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\Traits\HasRoles;

/**
 * Compte d'un membre du réseau (base du tenant).
 *
 * Le slug (prénom-nom, unique) sert d'adresse à la fiche : /reseau/aminata-diallo.
 *
 * Le compte superadmin est protégé : il ne peut être ni supprimé, ni désactivé, ni privé
 * de son rôle (ProtectedSuperAdminException).
 */
#[Fillable(['first_name', 'last_name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUniqueSlug, Notifiable;

    use HasRoles {
        removeRole as protected removeRoleUnprotected;
        syncRoles as protected syncRolesUnprotected;
    }

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if ($user->isDirty('is_active') && ! $user->is_active && $user->isSuperAdmin()) {
                throw ProtectedSuperAdminException::cannotDeactivate();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Nom complet affiché (prénom puis nom).
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim("{$this->first_name} {$this->last_name}"));
    }

    /**
     * Profil de médiatrice affiché dans l'annuaire (comptes ayant le rôle member).
     *
     * @return HasOne<MemberProfile, $this>
     */
    public function memberProfile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    /**
     * URL de la photo de la médiatrice (route protégée), versionnée par la date de mise à jour
     * du profil pour un cache navigateur long ; null sans photo. Profil chargé au préalable.
     */
    public function photoUrl(): ?string
    {
        $profile = $this->memberProfile;

        return $profile?->photo_path === null ? null : route('members.photo', [
            'user' => $this->id,
            'v' => $profile->updated_at?->getTimestamp(),
        ], false);
    }

    /**
     * Médiatrices enregistrées avec le marque-page.
     *
     * @return BelongsToMany<User, $this>
     */
    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'user_id', 'member_id')->withTimestamps();
    }

    /**
     * Comptes qui ont enregistré cette médiatrice dans leurs favoris.
     *
     * @return BelongsToMany<User, $this>
     */
    public function favoredBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'member_id', 'user_id');
    }

    /**
     * @return BelongsToMany<Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)
            ->using(EventParticipant::class)
            ->withPivot(['role']);
    }

    /**
     * Ressources de la bibliothèque enregistrées avec le signet.
     *
     * @return BelongsToMany<\App\Models\Resource, $this>
     */
    public function savedResources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'resource_saves')->withTimestamps();
    }

    /**
     * Conversations de la messagerie interne.
     *
     * @return BelongsToMany<Conversation, $this>
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)->withPivot(['last_read_message_id', 'emailed_at', 'cleared_message_id']);
    }

    /**
     * Prochain événement, quand la requête sélectionne `next_event_id` (MemberDirectory).
     *
     * @return BelongsTo<Event, $this>
     */
    public function nextEvent(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'next_event_id');
    }

    /**
     * Vérifié avant l'événement « deleting » : laravel-permission y retire déjà les rôles.
     */
    public function delete(): ?bool
    {
        if ($this->isSuperAdmin()) {
            throw ProtectedSuperAdminException::cannotDelete();
        }

        return parent::delete();
    }

    /**
     * Slug : prénom-nom sans accents.
     */
    protected function slugSource(): string
    {
        return $this->name;
    }

    protected function slugFallback(): string
    {
        return 'membre';
    }

    protected function slugDependsOn(): array
    {
        return ['first_name', 'last_name'];
    }

    public function isSuperAdmin(): bool
    {
        return $this->exists && $this->hasRole(Role::SuperAdmin->value);
    }

    /**
     * @param  string|int|array<mixed>|RoleModel|BackedEnum  ...$role
     */
    public function removeRole(...$role): static
    {
        if ($this->isSuperAdmin() && in_array(Role::SuperAdmin->value, self::roleNames($role), true)) {
            throw ProtectedSuperAdminException::cannotLoseRole();
        }

        return $this->removeRoleUnprotected(...$role);
    }

    /**
     * @param  string|int|array<mixed>|RoleModel|BackedEnum  ...$roles
     */
    public function syncRoles(...$roles): static
    {
        if ($this->isSuperAdmin() && ! in_array(Role::SuperAdmin->value, self::roleNames($roles), true)) {
            throw ProtectedSuperAdminException::cannotLoseRole();
        }

        return $this->syncRolesUnprotected(...$roles);
    }

    /**
     * Le lien de réinitialisation pointe vers la SPA du tenant (voir la notification).
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Noms des rôles passés à removeRole / syncRoles (chaînes, enums ou modèles).
     *
     * @param  array<mixed>  $roles
     * @return list<string>
     */
    private static function roleNames(array $roles): array
    {
        return array_values(array_map(
            fn (mixed $role): string => match (true) {
                $role instanceof RoleModel => $role->name,
                $role instanceof BackedEnum => (string) $role->value,
                default => (string) $role,
            },
            Arr::flatten($roles),
        ));
    }
}
