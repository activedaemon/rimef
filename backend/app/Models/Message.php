<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactSubject;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message de la messagerie interne. `subject` : objet choisi dans la fenêtre « Contacter ».
 */
#[Fillable(['user_id', 'subject', 'body'])]
class Message extends Model
{
    protected function casts(): array
    {
        return [
            'subject' => ContactSubject::class,
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
}
