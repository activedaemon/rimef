<?php

use App\Enums\Role;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    $this->fatou = User::factory()->create(['first_name' => 'Fatou', 'last_name' => 'Ndiaye']);
    $this->fatou->assignRole(Role::Member);
    $this->aminata = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo']);
    $this->aminata->assignRole(Role::Member);
    $this->leila = User::factory()->create(['first_name' => 'Leila', 'last_name' => 'Bouzid']);
    $this->leila->assignRole(Role::Member);

    foreach ([$this->fatou, $this->leila] as $sender) {
        $this->actingAs($sender)->postJson($this->tenantUrl('/api/members/aminata-diallo/contact'), [
            'body' => "Bonjour, ici {$sender->first_name}.",
        ])->assertCreated();
        $this->travel(1)->seconds();
    }
});

it('requires an open session', function () {
    auth()->forgetGuards();
    $this->getJson($this->tenantUrl('/api/notifications'))->assertUnauthorized();
});

it('lists the notifications of the bell, most recent first', function () {
    $this->actingAs($this->aminata)
        ->getJson($this->tenantUrl('/api/notifications'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.title', 'Nouveau message de Leila Bouzid')
        ->assertJsonPath('data.0.text', 'Bonjour, ici Leila.')
        ->assertJsonPath('data.0.is_read', false)
        ->assertJsonPath('data.1.title', 'Nouveau message de Fatou Ndiaye');
});

it('shows only the 3 most recent notifications, read or not', function () {
    foreach (['Mariam', 'Claire', 'Sarah'] as $firstName) {
        $sender = User::factory()->create(['first_name' => $firstName, 'last_name' => 'Test']);
        $sender->assignRole(Role::Member);
        $this->actingAs($sender)->postJson($this->tenantUrl('/api/members/aminata-diallo/contact'), [
            'body' => "Bonjour, ici {$firstName}.",
        ])->assertCreated();
        $this->travel(1)->seconds();
    }

    $this->aminata->notifications()->latest()->first()->markAsRead();

    $this->actingAs($this->aminata)
        ->getJson($this->tenantUrl('/api/notifications'))
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.is_read', true)
        ->assertJsonPath('data.0.title', 'Message de Sarah Test')
        ->assertJsonPath('data.2.title', 'Nouveau message de Mariam Test');
    $this->getJson($this->tenantUrl('/api/notifications/unread-count'))->assertJsonPath('data.notifications', 4);
});

it('marks one or all notifications as read', function () {
    $this->actingAs($this->aminata);
    $id = $this->getJson($this->tenantUrl('/api/notifications'))->json('data.0.id');

    $this->postJson($this->tenantUrl("/api/notifications/{$id}/read"))->assertNoContent();
    $this->getJson($this->tenantUrl('/api/notifications/unread-count'))->assertJsonPath('data.notifications', 1);
    // Une notification lue reste dans la cloche, marquée lue
    $this->getJson($this->tenantUrl('/api/notifications'))
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.is_read', true);

    $this->postJson($this->tenantUrl('/api/notifications/read-all'))->assertNoContent();
    $this->getJson($this->tenantUrl('/api/notifications/unread-count'))
        ->assertJsonPath('data.notifications', 0)
        // Les messages restent non lus tant que les conversations ne sont pas ouvertes
        ->assertJsonPath('data.messages', 2);
    $this->getJson($this->tenantUrl('/api/notifications'))
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.1.is_read', true);
});

it('drops « nouveau » from the title once the notification is read', function () {
    $this->actingAs($this->fatou)->postJson($this->tenantUrl('/api/members/aminata-diallo/contact'), [
        'body' => 'Deuxième message.',
    ])->assertCreated();

    $this->actingAs($this->aminata);
    $fatou = fn () => collect($this->getJson($this->tenantUrl('/api/notifications'))->json('data'))
        ->firstWhere('sender_name', 'Fatou Ndiaye');
    $leila = fn () => collect($this->getJson($this->tenantUrl('/api/notifications'))->json('data'))
        ->firstWhere('sender_name', 'Leila Bouzid');

    expect($fatou()['title'])->toBe('2 nouveaux messages de Fatou Ndiaye');

    $this->postJson($this->tenantUrl('/api/notifications/read-all'))->assertNoContent();

    expect($fatou()['title'])->toBe('Messages de Fatou Ndiaye')
        ->and($leila()['title'])->toBe('Message de Leila Bouzid');
});

it('derives the read title of older entries from their title', function () {
    expect(NewMessageNotification::readTitle(['title' => '3 nouveaux messages de David Gautier', 'sender_name' => 'David Gautier']))
        ->toBe('Messages de David Gautier')
        ->and(NewMessageNotification::readTitle(['title' => 'Nouveau message de David Gautier', 'sender_name' => 'David Gautier']))
        ->toBe('Message de David Gautier');
});

it('does not let anyone read the notifications of someone else', function () {
    $id = $this->aminata->notifications()->value('id');

    $this->actingAs($this->fatou)
        ->postJson($this->tenantUrl("/api/notifications/{$id}/read"))
        ->assertNotFound();
});
