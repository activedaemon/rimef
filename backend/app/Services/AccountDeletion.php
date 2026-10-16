<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Suppression définitive d'un compte (superadmin seul : demande RGPD, compte créé par erreur).
 *
 * Effacé : identité, email, photo, fiche, zones, expertises, liens, favoris, participations,
 * recommandations, sessions et jetons. Gardé : le nombre de participantes de chaque
 * événement (events.former_attendees_count), les messages déjà échangés (sans autrice,
 * affichés « Compte supprimé ») et les ressources qu'elle a publiées, sans autrice.
 * Les clés étrangères (cascade ou mise à null) font le reste.
 */
class AccountDeletion
{
    public function delete(User $account): void
    {
        $photoPath = $account->memberProfile?->photo_path;

        DB::transaction(function () use ($account): void {
            Event::query()
                ->whereIn('id', DB::table('event_user')->where('user_id', $account->getKey())->select('event_id'))
                ->increment('former_attendees_count');

            // Entrées de la cloche des autres participantes qui portent son nom
            $conversationIds = DB::table('conversation_user')->where('user_id', $account->getKey())->pluck('conversation_id');
            if ($conversationIds->isNotEmpty()) {
                DatabaseNotification::query()
                    ->where('type', NewMessageNotification::class)
                    ->whereIn('data->conversation_id', $conversationIds->all())
                    ->delete();
            }

            $account->notifications()->delete();
            DB::table('sessions')->where('user_id', $account->getKey())->delete();
            DB::table('password_reset_tokens')->where('email', $account->email)->delete();
            DB::table('invitation_tokens')->where('email', $account->email)->delete();

            $account->delete();
        });

        if ($photoPath !== null) {
            Storage::disk('local')->delete($photoPath);
        }
    }
}
