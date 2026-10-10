<?php

use App\Enums\Role;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\NewMessageNotification;
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
            'subject' => 'co_mediation',
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
        ->assertJsonPath('data.0.subject', 'Proposition de co-médiation')
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
    ($this->contact)($this->fatou, 'aminata-diallo', ['subject' => 'inconnu', 'body' => str_repeat('a', 2001)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['subject', 'body']);
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
        ->assertJsonPath('data.subject', null)
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

it('sends the email without revealing any address', function () {
    Mail::fake();
    ($this->contact)($this->fatou, 'aminata-diallo');

    $notification = $this->aminata->notifications()->sole();
    $mail = NewMessageNotification::for(Conversation::sole()->messages()->sole(), 1, true)->toMail($this->aminata);
    $rendered = (string) $mail->render();

    expect($mail->subject)->toBe('Nouveau message de Fatou Ndiaye sur RIMeF')
        ->and($rendered)->toContain('Proposition de co-médiation')
        ->and($rendered)->toContain('http://'.$this->tenant->domains()->value('domain').$notification->data['path'])
        ->and($rendered)->not->toContain($this->fatou->email)
        ->and($rendered)->not->toContain($this->aminata->email);
});

it('stores queued jobs in the central database, even from a tenant', function () {
    config(['queue.default' => 'database']);
    $central = config('tenancy.database.central_connection');

    $this->aminata->notify(new NewMessageNotification(1, 'Fatou Ndiaye', null, null, 'Bonjour', 1, 'http://x', ['mail']));

    expect(config('queue.connections.database.connection'))->toBe($central)
        ->and(DB::connection($central)->table('jobs')->count())->toBe(1);
});
