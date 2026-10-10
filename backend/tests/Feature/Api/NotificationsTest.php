<?php

use App\Enums\Role;
use App\Models\User;
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
            'subject' => 'peer_exchange',
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

it('marks one or all notifications as read', function () {
    $this->actingAs($this->aminata);
    $id = $this->getJson($this->tenantUrl('/api/notifications'))->json('data.0.id');

    $this->postJson($this->tenantUrl("/api/notifications/{$id}/read"))->assertNoContent();
    $this->getJson($this->tenantUrl('/api/notifications/unread-count'))->assertJsonPath('data.notifications', 1);

    $this->postJson($this->tenantUrl('/api/notifications/read-all'))->assertNoContent();
    $this->getJson($this->tenantUrl('/api/notifications/unread-count'))
        ->assertJsonPath('data.notifications', 0)
        // Les messages restent non lus tant que les conversations ne sont pas ouvertes
        ->assertJsonPath('data.messages', 2);
});

it('does not let anyone read the notifications of someone else', function () {
    $id = $this->aminata->notifications()->value('id');

    $this->actingAs($this->fatou)
        ->postJson($this->tenantUrl("/api/notifications/{$id}/read"))
        ->assertNotFound();
});
