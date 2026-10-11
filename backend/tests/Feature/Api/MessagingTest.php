<?php

use App\Enums\Role;
use App\Models\Conversation;
use App\Models\MemberProfile;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\Messaging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    $this->fatou = User::factory()->create(['first_name' => 'Fatou', 'last_name' => 'Ndiaye']);
    $this->fatou->assignRole(Role::Member);
    $this->aminata = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo']);
    $this->aminata->assignRole(Role::Member);

    // Fenêtre « Contacter » d'une fiche
    $this->contact = fn (User $from, string $slug, array $payload = []) => $this->actingAs($from)
        ->postJson($this->tenantUrl("/api/members/{$slug}/contact"), [
            'body' => 'Bonjour Aminata, seriez-vous disponible en novembre ?',
            ...$payload,
        ]);
});

it('requires an open session', function () {
    $this->postJson($this->tenantUrl('/api/members/aminata-diallo/contact'))->assertUnauthorized();
    $this->getJson($this->tenantUrl('/api/conversations'))->assertUnauthorized();
});

it('starts a conversation from the contact window', function () {
    $response = ($this->contact)($this->fatou, 'aminata-diallo')->assertCreated();

    $conversation = Conversation::sole();
    expect($response->json('data.conversation_id'))->toBe($conversation->id)
        ->and($conversation->participants->pluck('id')->sort()->values()->all())
        ->toBe([$this->fatou->id, $this->aminata->id]);

    $this->getJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages"))
        ->assertOk()
        ->assertJsonPath('data.0.body', 'Bonjour Aminata, seriez-vous disponible en novembre ?')
        ->assertJsonPath('data.0.is_mine', true)
        ->assertJsonPath('meta.has_more', false);
});

it('reuses the same conversation for the same pair, in both directions', function () {
    ($this->contact)($this->fatou, 'aminata-diallo')->assertCreated();
    ($this->contact)($this->aminata, 'fatou-ndiaye')->assertCreated();

    expect(Conversation::count())->toBe(1)
        ->and(Conversation::sole()->messages()->count())->toBe(2);
});

it('validates the contact window', function () {
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => str_repeat('a', 2001)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => ''])
        ->assertJsonValidationErrors(['body']);
});

it('refuses to contact oneself or an account outside the directory', function () {
    $adminOnly = User::factory()->create(['first_name' => 'Admin', 'last_name' => 'Seule']);
    $adminOnly->assignRole(Role::Admin);

    ($this->contact)($this->fatou, 'fatou-ndiaye')->assertUnprocessable();
    ($this->contact)($this->fatou, 'admin-seule')->assertNotFound();
    ($this->contact)($this->fatou, 'inconnue')->assertNotFound();
});

it('limits the contact window to 10 messages per hour', function () {
    foreach (range(1, 10) as $ignored) {
        ($this->contact)($this->fatou, 'aminata-diallo')->assertCreated();
    }

    ($this->contact)($this->fatou, 'aminata-diallo')->assertTooManyRequests();
});

it('hides a conversation from non participants', function () {
    ($this->contact)($this->fatou, 'aminata-diallo');
    $id = Conversation::sole()->id;
    $outsider = User::factory()->create();
    $outsider->assignRole(Role::Member);

    $this->actingAs($outsider);
    $this->getJson($this->tenantUrl("/api/conversations/{$id}"))->assertNotFound();
    $this->getJson($this->tenantUrl("/api/conversations/{$id}/messages"))->assertNotFound();
    $this->postJson($this->tenantUrl("/api/conversations/{$id}/messages"), ['body' => 'Coucou'])->assertNotFound();
    $this->putJson($this->tenantUrl("/api/conversations/{$id}/read"))->assertNotFound();
});

it('lists the conversations with the contact and the unread count', function () {
    ($this->contact)($this->fatou, 'aminata-diallo');
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Deuxième message']);

    $this->actingAs($this->aminata)
        ->getJson($this->tenantUrl('/api/conversations'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.contact.slug', 'fatou-ndiaye')
        ->assertJsonPath('data.0.contact.has_profile', true)
        ->assertJsonPath('data.0.last_message.excerpt', 'Deuxième message')
        ->assertJsonPath('data.0.last_message.is_mine', false)
        ->assertJsonPath('data.0.unread_count', 2);

    $this->actingAs($this->fatou)
        ->getJson($this->tenantUrl('/api/conversations'))
        ->assertJsonPath('data.0.contact.slug', 'aminata-diallo')
        ->assertJsonPath('data.0.unread_count', 0);
});

it('searches the conversations and filters the unread ones, with the counters', function () {
    $leila = User::factory()->create(['first_name' => 'Leïla', 'last_name' => 'Bouzid']);
    $leila->assignRole(Role::Member);
    MemberProfile::factory()->for($leila)->create(['city' => 'Rabat', 'country_code' => 'MA']);
    MemberProfile::factory()->for($this->fatou)->create(['city' => 'Abidjan', 'country_code' => 'CI']);

    // Aminata a deux conversations : Fatou (non lue) et Leïla (lue, au sujet de la gouvernance locale)
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Le compte rendu de l’atelier est prêt.']);
    ($this->contact)($leila, 'aminata-diallo', ['body' => 'Bonjour, parlons gouvernance locale']);
    app(Messaging::class)->markAsRead(Conversation::where('pair_key', Conversation::pairKey($leila, $this->aminata))->sole(), $this->aminata);

    $this->actingAs($this->aminata);
    $search = fn (string $query) => $this->getJson($this->tenantUrl('/api/conversations?'.$query))
        ->assertOk()->json('data.*.contact.slug');

    expect($search(''))->toBe(['leila-bouzid', 'fatou-ndiaye'])
        ->and($search('q=ndiaye'))->toBe(['fatou-ndiaye'])
        ->and($search('q=rabat'))->toBe(['leila-bouzid'])
        ->and($search('q=maroc'))->toBe(['leila-bouzid'])
        ->and($search('q=compte+rendu'))->toBe(['fatou-ndiaye'])
        ->and($search('q=gouvernance'))->toBe(['leila-bouzid'])
        ->and($search('q=diallo'))->toBe([])
        ->and($search('unread=1'))->toBe(['fatou-ndiaye']);

    $this->getJson($this->tenantUrl('/api/conversations?q=rabat'))
        ->assertJsonPath('data.0.contact.place', 'Rabat, Maroc')
        ->assertJsonPath('meta.total_conversations', 2)
        ->assertJsonPath('meta.unread_conversations', 1);
});

it('never finds the conversations of other members', function () {
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Message confidentiel']);
    $other = User::factory()->create();
    $other->assignRole(Role::Member);

    $this->actingAs($other)
        ->getJson($this->tenantUrl('/api/conversations?q=confidentiel'))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total_conversations', 0);
});

it('marks the conversation as read when it is opened', function () {
    ($this->contact)($this->fatou, 'aminata-diallo');
    $id = Conversation::sole()->id;

    $this->actingAs($this->aminata);
    $this->getJson($this->tenantUrl('/api/notifications/unread-count'))
        ->assertJson(['data' => ['notifications' => 1, 'messages' => 1]]);

    $this->putJson($this->tenantUrl("/api/conversations/{$id}/read"))->assertNoContent();

    $this->getJson($this->tenantUrl('/api/notifications/unread-count'))
        ->assertJson(['data' => ['notifications' => 0, 'messages' => 0]]);
    $this->getJson($this->tenantUrl('/api/conversations'))->assertJsonPath('data.0.unread_count', 0);
});

it('replies in a conversation', function () {
    ($this->contact)($this->fatou, 'aminata-diallo');
    $id = Conversation::sole()->id;

    $this->actingAs($this->aminata)
        ->postJson($this->tenantUrl("/api/conversations/{$id}/messages"), ['body' => 'Avec plaisir !'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Avec plaisir !')
        ->assertJsonPath('data.is_mine', true);

    $this->actingAs($this->fatou)
        ->getJson($this->tenantUrl("/api/conversations/{$id}/messages"))
        ->assertJsonPath('data.0.body', 'Avec plaisir !')
        ->assertJsonPath('data.0.is_mine', false);
});

it('pages the messages, most recent first', function () {
    ($this->contact)($this->fatou, 'aminata-diallo');
    $conversation = Conversation::sole();
    foreach (range(1, 31) as $number) {
        $conversation->messages()->create(['user_id' => $this->fatou->id, 'body' => "Message {$number}"]);
    }

    $this->actingAs($this->aminata);
    $first = $this->getJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages"))
        ->assertJsonCount(30, 'data')
        ->assertJsonPath('data.0.body', 'Message 31')
        ->assertJsonPath('meta.has_more', true);

    $before = $first->json('data.29.id');
    $this->getJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages?before={$before}"))
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.has_more', false);
});

it('lets the author edit her message for 15 minutes', function () {
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Bonjour Aminata, dispnible ?']);
    $conversation = Conversation::sole();
    $message = $conversation->messages()->sole();
    $url = $this->tenantUrl("/api/conversations/{$conversation->id}/messages/{$message->id}");

    $this->actingAs($this->fatou)
        ->getJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages"))
        ->assertJsonPath('data.0.can_edit', true)
        ->assertJsonPath('data.0.is_edited', false);

    $this->patchJson($url, ['body' => 'Bonjour Aminata, disponible ?'])
        ->assertOk()
        ->assertJsonPath('data.body', 'Bonjour Aminata, disponible ?')
        ->assertJsonPath('data.is_edited', true);

    // La cloche d'Aminata reprend le texte corrigé
    expect($this->aminata->notifications()->sole()->data['text'])->toBe('Bonjour Aminata, disponible ?');

    $this->travel(16)->minutes();
    $this->patchJson($url, ['body' => 'Trop tard'])->assertUnprocessable();
    $this->getJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages"))
        ->assertJsonPath('data.0.can_edit', false);
});

it('lets only the author edit or delete a message', function () {
    ($this->contact)($this->fatou, 'aminata-diallo');
    $conversation = Conversation::sole();
    $message = $conversation->messages()->sole();
    $url = $this->tenantUrl("/api/conversations/{$conversation->id}/messages/{$message->id}");
    $outsider = User::factory()->create();
    $outsider->assignRole(Role::Member);

    $this->actingAs($this->aminata)->patchJson($url, ['body' => 'Piraté'])->assertForbidden();
    $this->actingAs($this->aminata)->deleteJson($url)->assertForbidden();
    $this->actingAs($outsider)->patchJson($url, ['body' => 'Piraté'])->assertNotFound();
    $this->actingAs($outsider)->deleteJson($url)->assertNotFound();
    $this->actingAs($this->fatou)->patchJson($url, ['body' => ''])->assertJsonValidationErrors(['body']);

    expect($message->fresh()->body)->toBe('Bonjour Aminata, seriez-vous disponible en novembre ?');
});

it('deletes a message: « Message supprimé » for both, text erased, not unread any more', function () {
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Premier']);
    $this->travel(1)->seconds();
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Second, à supprimer']);
    $conversation = Conversation::sole();
    $second = $conversation->messages()->latest('id')->first();
    $url = $this->tenantUrl("/api/conversations/{$conversation->id}/messages/{$second->id}");

    $this->actingAs($this->fatou)->deleteJson($url)
        ->assertOk()
        ->assertJsonPath('data.is_deleted', true)
        ->assertJsonPath('data.body', null)
        ->assertJsonPath('data.can_edit', false);
    $this->deleteJson($url)->assertUnprocessable();
    $this->patchJson($url, ['body' => 'Retour'])->assertUnprocessable();

    expect($second->fresh()->body)->toBe('');

    $this->actingAs($this->aminata)
        ->getJson($this->tenantUrl('/api/conversations'))
        ->assertJsonPath('data.0.last_message.excerpt', 'Message supprimé')
        ->assertJsonPath('data.0.unread_count', 1);
    $this->getJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages"))
        ->assertJsonPath('data.0.is_deleted', true)
        ->assertJsonPath('data.0.body', null);
    $this->getJson($this->tenantUrl('/api/notifications/unread-count'))->assertJsonPath('data.messages', 1);

    // La cloche ne compte plus que le premier message
    $entry = $this->aminata->notifications()->sole();
    expect($entry->data['title'])->toBe('Nouveau message de Fatou Ndiaye')
        ->and($entry->data['text'])->toBe('Premier');
});

it('removes the bell entry when the only unread message is deleted', function () {
    ($this->contact)($this->fatou, 'aminata-diallo');
    $conversation = Conversation::sole();
    $message = $conversation->messages()->sole();

    $this->actingAs($this->fatou)
        ->deleteJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages/{$message->id}"))
        ->assertOk();

    expect($this->aminata->notifications()->count())->toBe(0);
});

it('deletes a conversation for me only, the other keeps it', function () {
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Premier']);
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Second']);
    $conversation = Conversation::sole();

    $this->actingAs($this->aminata)
        ->deleteJson($this->tenantUrl("/api/conversations/{$conversation->id}"))
        ->assertNoContent();

    $this->getJson($this->tenantUrl('/api/conversations'))
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total_conversations', 0);
    $this->getJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages"))->assertJsonCount(0, 'data');
    $this->getJson($this->tenantUrl('/api/notifications/unread-count'))
        ->assertJsonPath('data.messages', 0)
        ->assertJsonPath('data.notifications', 0);
    $this->getJson($this->tenantUrl('/api/members/fatou-ndiaye'))->assertJsonPath('data.conversation', null);

    // Fatou garde tout
    $this->actingAs($this->fatou)
        ->getJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages"))
        ->assertJsonCount(2, 'data');
    expect($conversation->messages()->count())->toBe(2);
});

it('shows the conversation again with only the new messages', function () {
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Ancien']);
    $conversation = Conversation::sole();
    $this->actingAs($this->aminata)->deleteJson($this->tenantUrl("/api/conversations/{$conversation->id}"));

    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Nouveau']);

    $this->actingAs($this->aminata)
        ->getJson($this->tenantUrl('/api/conversations'))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.unread_count', 1);
    $this->getJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages"))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.body', 'Nouveau');
});

it('erases the messages deleted by both, then the conversation', function () {
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Premier']);
    $conversation = Conversation::sole();
    $this->actingAs($this->aminata)->deleteJson($this->tenantUrl("/api/conversations/{$conversation->id}"));
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Après la suppression d’Aminata']);

    // Fatou supprime à son tour : seul le premier message a été supprimé par les deux
    $this->actingAs($this->fatou)->deleteJson($this->tenantUrl("/api/conversations/{$conversation->id}"))->assertNoContent();
    expect($conversation->messages()->pluck('body')->all())->toBe(['Après la suppression d’Aminata']);

    $this->actingAs($this->aminata)->deleteJson($this->tenantUrl("/api/conversations/{$conversation->id}"))->assertNoContent();
    expect(Conversation::count())->toBe(0)
        ->and(DB::table('messages')->count())->toBe(0)
        ->and(DB::table('conversation_user')->count())->toBe(0);
});

it('does not let a non participant delete a conversation', function () {
    ($this->contact)($this->fatou, 'aminata-diallo');
    $outsider = User::factory()->create();
    $outsider->assignRole(Role::Member);

    $this->actingAs($outsider)
        ->deleteJson($this->tenantUrl('/api/conversations/'.Conversation::sole()->id))
        ->assertNotFound();
});

it('sends one email per conversation until it is read, and keeps one bell entry', function () {
    Notification::fake();

    ($this->contact)($this->fatou, 'aminata-diallo');
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Deuxième']);

    Notification::assertSentToTimes($this->aminata, NewMessageNotification::class, 2);
    $sent = Notification::sent($this->aminata, NewMessageNotification::class);
    expect($sent[0]->via($this->aminata))->toBe(['database', 'mail'])
        ->and($sent[1]->via($this->aminata))->toBe(['database'])
        ->and($sent[1]->toArray($this->aminata)['title'])->toBe('2 nouveaux messages de Fatou Ndiaye');

    $this->actingAs($this->aminata)->putJson($this->tenantUrl('/api/conversations/'.Conversation::sole()->id.'/read'));
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Troisième']);

    expect(Notification::sent($this->aminata, NewMessageNotification::class)[2]->via($this->aminata))
        ->toBe(['database', 'mail']);
    Notification::assertNotSentTo($this->fatou, NewMessageNotification::class);
});

it('replaces the unread bell entry of a conversation', function () {
    ($this->contact)($this->fatou, 'aminata-diallo');
    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Deuxième']);

    $notifications = $this->aminata->notifications()->get();
    expect($notifications)->toHaveCount(1)
        ->and($notifications->first()->data['title'])->toBe('2 nouveaux messages de Fatou Ndiaye')
        ->and($notifications->first()->data['path'])->toBe('/messages/'.Conversation::sole()->id);
});

it('keeps one bell entry per conversation, even after it was read', function () {
    ($this->contact)($this->fatou, 'aminata-diallo');
    $this->aminata->notifications()->sole()->markAsRead();

    ($this->contact)($this->fatou, 'aminata-diallo', ['body' => 'Encore un message']);

    $notification = $this->aminata->notifications()->sole();
    expect($notification->read_at)->toBeNull()
        ->and($notification->data['text'])->toBe('Encore un message');
});

it('sends the email without revealing any address', function () {
    Mail::fake();
    ($this->contact)($this->fatou, 'aminata-diallo');

    $notification = $this->aminata->notifications()->sole();
    $mail = NewMessageNotification::for(Conversation::sole()->messages()->sole(), 1, true)->toMail($this->aminata);
    $rendered = (string) $mail->render();

    expect($mail->subject)->toBe('Fatou Ndiaye vous a écrit sur RIMeF')
        ->and($rendered)->toContain('seriez-vous disponible en novembre')
        ->and($rendered)->toContain('http://'.$this->tenant->domains()->value('domain').$notification->data['path'])
        ->and($rendered)->not->toContain($this->fatou->email)
        ->and($rendered)->not->toContain($this->aminata->email);
});

it('stores queued jobs in the central database, even from a tenant', function () {
    config(['queue.default' => 'database']);
    $central = config('tenancy.database.central_connection');

    $this->aminata->notify(new NewMessageNotification(1, 'Fatou Ndiaye', null, 'Bonjour', 1, 'http://x', ['mail']));

    expect(config('queue.connections.database.connection'))->toBe($central)
        ->and(DB::connection($central)->table('jobs')->count())->toBe(1);
});
