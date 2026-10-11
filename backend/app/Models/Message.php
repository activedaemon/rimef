<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message de la messagerie interne. Son autrice peut le modifier pendant 15 minutes
 * (`edited_at`) et le supprimer à tout moment : il reste alors à sa place, texte vidé
 * (`deleted_at`, sans SoftDeletes pour garder « Message supprimé » dans le fil).
 */
#[Fillable(['user_id', 'body', 'edited_at', 'deleted_at'])]
class Message extends Model
{
    public const EDIT_WINDOW_MINUTES = 15;

    protected function casts(): array
    {
        return [
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Expéditrice (null si son compte a été supprimé).
     *
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }

    public function isFrom(User $user): bool
    {
        return $this->user_id === $user->getKey();
    }

    /**
     * Modifiable par son autrice pendant 15 minutes après l'envoi, sauf s'il a été supprimé.
     */
    public function isEditableBy(User $user): bool
    {
        return $this->isFrom($user)
            && ! $this->isDeleted()
            && $this->created_at->gt(now()->subMinutes(self::EDIT_WINDOW_MINUTES));
    }
}
