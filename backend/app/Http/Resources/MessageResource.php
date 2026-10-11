<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Message d'une conversation, vu par la personne connectée.
 *
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // Message supprimé : plus de texte, seulement « Message supprimé » dans le fil
            'body' => $this->isDeleted() ? null : $this->body,
            'sent_at' => $this->created_at->toIso8601String(),
            'is_mine' => $this->isFrom($request->user()),
            'is_edited' => $this->edited_at !== null,
            'is_deleted' => $this->isDeleted(),
            'can_edit' => $this->isEditableBy($request->user()),
        ];
    }
}
