<?php

use App\Enums\Role;
use App\Models\User;
use App\Services\Messaging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    Notification::fake();

    $this->superAdmin = User::factory()->create(['first_name' => 'David', 'last_name' => 'Gautier']);
    $this->superAdmin->assignRole(Role::SuperAdmin);

    $this->admin = User::factory()->create(['first_name' => 'Hélène', 'last_name' => 'Marchand']);
    $this->admin->assignRole(Role::Admin);

    $this->member = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo']);
    $this->member->assignRole(Role::Member);
});

it('lets every active account write to any other, team included', function (string $from, string $to) {
    $this->actingAs($this->{$from})->postJson($this->tenantUrl('/api/conversations'), [
        'recipient_id' => $this->{$to}->id,
        'body' => 'Bonjour',
    ])->assertCreated()->assertJsonStructure(['data' => ['conversation_id']]);
})->with([
    'membre → administratrice' => ['member', 'admin'],
    'membre → superadmin' => ['member', 'superAdmin'],
    'superadmin → membre' => ['superAdmin', 'member'],
]);

it('reuses the existing conversation', function () {
    $first = $this->actingAs($this->member)->postJson($this->tenantUrl('/api/conversations'), ['recipient_id' => $this->admin->id, 'body' => 'Un'])->json('data.conversation_id');
    $second = $this->actingAs($this->member)->postJson($this->tenantUrl('/api/conversations'), ['recipient_id' => $this->admin->id, 'body' => 'Deux'])->json('data.conversation_id');

    expect($second)->toBe($first);
});

it('refuses to write to a deactivated account or to oneself', function () {
    $this->admin->forceFill(['is_active' => false])->save();

    $this->actingAs($this->member)->postJson($this->tenantUrl('/api/conversations'), ['recipient_id' => $this->admin->id, 'body' => 'Bonjour'])
        ->assertForbidden();
    $this->actingAs($this->member)->postJson($this->tenantUrl('/api/conversations'), ['recipient_id' => $this->member->id, 'body' => 'Bonjour'])
        ->assertUnprocessable();
});

it('turns a conversation read-only when the other account is deactivated or deleted', function () {
    $conversation = app(Messaging::class)->conversationBetween($this->member, $this->admin);
    app(Messaging::class)->send($conversation, $this->admin, 'Bonjour Aminata');

    $this->actingAs($this->member)->getJson($this->tenantUrl("/api/conversations/{$conversation->id}"))
        ->assertJsonPath('data.can_reply', true)
        ->assertJsonPath('data.contact.is_team', true)
        ->assertJsonPath('data.contact.roles', ['admin']);

    $this->admin->forceFill(['is_active' => false])->save();

    $this->actingAs($this->member)->getJson($this->tenantUrl("/api/conversations/{$conversation->id}"))
        ->assertJsonPath('data.can_reply', false);
    $this->actingAs($this->member)->postJson($this->tenantUrl("/api/conversations/{$conversation->id}/messages"), ['body' => 'Merci'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Vous ne pouvez plus répondre à cette conversation.');

    $this->admin->delete();

    $this->actingAs($this->member)->getJson($this->tenantUrl("/api/conversations/{$conversation->id}"))
        ->assertJsonPath('data.contact', null)
        ->assertJsonPath('data.can_reply', false);
});

it('lists the active team, without the viewer', function () {
    $inactive = User::factory()->create(['is_active' => false]);
    $inactive->assignRole(Role::Admin);

    $this->actingAs($this->admin)->getJson($this->tenantUrl('/api/team'))
        ->assertOk()
        ->assertJsonPath('data.*.name', ['David Gautier'])
        ->assertJsonPath('data.0.roles', ['superadmin']);

    $this->actingAs($this->member)->getJson($this->tenantUrl('/api/team'))
        ->assertJsonPath('data.*.name', ['David Gautier', 'Hélène Marchand'])
        ->assertJsonPath('data.1.has_profile', false);
});
