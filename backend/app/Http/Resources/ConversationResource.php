<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Conversation;
use App\Models\User;
use App\Support\DirectoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * Conversation vue par la personne connectée : son interlocutrice, le dernier message et
 * le nombre de messages non lus. Participants (avec profil) et dernier message chargés au préalable.
 *
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $me = $request->user();
        /** @var User|null $other */
        $other = $this->participants->first(fn (User $user): bool => ! $user->is($me));
        $last = $this->latestMessage;

        return [
            'id' => $this->id,
            'contact' => $other === null ? null : [
                'id' => $other->id,
                'slug' => $other->slug,
                'name' => $other->name,
                'photo_url' => $other->photoUrl(),
                // « Dakar, Sénégal » (liste et en-tête de la conversation)
                'place' => $this->place($other),
                // Fiche visible dans l'annuaire : lien depuis la conversation
                'has_profile' => $other->is_active && $other->hasRole('member'),
            ],
            'last_message' => $last === null ? null : [
                'excerpt' => Str::limit((string) preg_replace('/\s+/u', ' ', $last->body), 120),
                'sent_at' => $last->created_at->toIso8601String(),
                'is_mine' => $last->user_id === $me->getKey(),
            ],
            'unread_count' => (int) ($this->unread_count ?? 0),
        ];
    }

    private function place(User $user): ?string
    {
        $profile = $user->memberProfile;
        $parts = array_filter([$profile?->city, DirectoryCatalog::countryName($profile?->country_code)]);

        return $parts === [] ? null : implode(', ', $parts);
    }
}
