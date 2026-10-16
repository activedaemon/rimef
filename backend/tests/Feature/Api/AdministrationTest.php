<?php

use App\Enums\Role;
use App\Models\Event;
use App\Models\MemberProfile;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Services\Messaging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\WithTenant;

uses(RefreshDatabase::class, WithTenant::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['first_name' => 'David', 'last_name' => 'Gautier']);
    $this->superAdmin->assignRole(Role::SuperAdmin);

    $this->admin = User::factory()->create(['first_name' => 'Hélène', 'last_name' => 'Marchand']);
    $this->admin->assignRole(Role::Admin);

    $this->otherAdmin = User::factory()->create(['first_name' => 'Claire', 'last_name' => 'Dubois']);
    $this->otherAdmin->assignRole([Role::Admin, Role::Member]);

    $this->member = User::factory()->create(['first_name' => 'Aminata', 'last_name' => 'Diallo', 'email' => 'aminata@example.org']);
    $this->member->assignRole(Role::Member);
});

/**
 * Compte invité, sans mot de passe, envoyé il y a $daysAgo jours.
 */
function invitedAccount(int $daysAgo = 1, Role $role = Role::Member): User
{
    $account = User::factory()->create(['password' => null, 'is_active' => false]);
    $account->forceFill(['invitation_pending' => true, 'invited_at' => now()->subDays($daysAgo)])->save();
    $account->assignRole($role);

    return $account;
}

describe('access', function () {
    it('is reserved to administrators', function () {
        $this->getJson($this->tenantUrl('/api/admin/accounts'))->assertUnauthorized();

        $this->actingAs($this->member)->getJson($this->tenantUrl('/api/admin/accounts'))->assertForbidden();
        $this->actingAs($this->admin)->getJson($this->tenantUrl('/api/admin/accounts'))->assertOk();
        $this->actingAs($this->superAdmin)->getJson($this->tenantUrl('/api/admin/accounts'))->assertOk();
    });

    it('refuses a deactivated administrator', function () {
        $this->admin->forceFill(['is_active' => false])->save();

        $this->actingAs($this->admin)->getJson($this->tenantUrl('/api/admin/accounts'))->assertForbidden();
    });
});

describe('list', function () {
    it('lists every account with its status, roles and the actions allowed to the viewer', function () {
        invitedAccount();

        $response = $this->actingAs($this->admin)->getJson($this->tenantUrl('/api/admin/accounts'))
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.facets.total', 5)
            ->assertJsonPath('meta.facets.status.2.value', 'pending')
            ->assertJsonPath('meta.facets.status.2.count', 1);

        $diallo = collect($response->json('data'))->firstWhere('last_name', 'Diallo');
        expect($diallo['status'])->toBe('active')
            ->and($diallo['roles'])->toBe(['member'])
            ->and($diallo['profile_slug'])->toBe('aminata-diallo')
            ->and($diallo['can'])->toBe([
                'toggle_activation' => true,
                'update_roles' => false,
                'delete' => false,
                'manage_invitation' => false,
                'send_message' => true,
            ]);

        $gautier = collect($response->json('data'))->firstWhere('last_name', 'Gautier');
        expect($gautier['is_protected'])->toBeTrue()
            ->and($gautier['can']['toggle_activation'])->toBeFalse()
            ->and($gautier['can']['send_message'])->toBeTrue();
    });

    it('filters by status, role and name or email, sorted by last name', function () {
        $this->member->forceFill(['is_active' => false])->save();
        $expired = invitedAccount(8);
        $expired->forceFill(['last_name' => 'Zola'])->save(); // tri par nom : après Diallo

        $this->actingAs($this->admin)->getJson($this->tenantUrl('/api/admin/accounts?status[]=inactive&status[]=expired'))
            ->assertOk()
            ->assertJsonPath('data.*.id', [$this->member->id, $expired->id]);

        $this->actingAs($this->admin)->getJson($this->tenantUrl('/api/admin/accounts?role[]=admin'))
            ->assertJsonPath('data.*.last_name', ['Dubois', 'Marchand']);

        $this->actingAs($this->admin)->getJson($this->tenantUrl('/api/admin/accounts?q=aminata@exa'))
            ->assertJsonPath('data.*.id', [$this->member->id]);
    });

    it('sorts by last login, never-logged accounts last', function () {
        $this->member->forceFill(['last_login_at' => now()->subDay()])->save();
        $this->admin->forceFill(['last_login_at' => now()])->save();

        $ids = $this->actingAs($this->admin)->getJson($this->tenantUrl('/api/admin/accounts?sort=last_login'))->json('data.*.id');

        expect(array_slice($ids, 0, 2))->toBe([$this->admin->id, $this->member->id]);
    });

    it('validates the filters', function () {
        $this->actingAs($this->admin)->getJson($this->tenantUrl('/api/admin/accounts?status[]=unknown&per_page=7'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status.0', 'per_page']);
    });
});

describe('profile summary', function () {
    it('tells how complete the profile is and what is missing', function () {
        MemberProfile::factory()->for($this->member)->create([
            'photo_path' => null,
            'job_title' => 'Conseillère',
            'bio' => null,
            'links' => null,
        ]);

        $summary = $this->actingAs($this->admin)->getJson($this->tenantUrl("/api/admin/accounts/{$this->member->id}"))
            ->assertOk()
            ->json('data.profile_summary');

        expect($summary['visible'])->toBeTrue()
            ->and($summary['missing'])->toContain('photo', 'biographie', 'liens', 'expertises', 'zones d’intervention')
            ->and($summary['completion'])->toBeLessThan(100)
            ->and($summary['upcoming_events'])->toBe(0);
    });

    it('shows the completion in the list and sorts the least complete profiles first', function () {
        MemberProfile::factory()->for($this->member)->create([
            'photo_path' => 'members/a.webp', 'job_title' => 'Conseillère', 'bio' => 'Bio', 'links' => null,
        ]);
        $other = User::factory()->create(['last_name' => 'Ba']);
        $other->assignRole(Role::Member);
        MemberProfile::factory()->for($other)->create(['photo_path' => null, 'job_title' => null, 'bio' => null, 'links' => null]);

        $rows = collect($this->actingAs($this->admin)->getJson($this->tenantUrl('/api/admin/accounts?sort=completion'))->json('data'));

        $diallo = $rows->firstWhere('id', $this->member->id);
        expect($diallo['profile_completion']['percent'])->toBeGreaterThan($rows->firstWhere('id', $other->id)['profile_completion']['percent'])
            ->and($diallo['profile_completion']['missing'])->toContain('liens')
            ->and($rows->firstWhere('id', $this->admin->id)['profile_completion'])->toBeNull()
            ->and($rows->pluck('id')->search($other->id))->toBeLessThan($rows->pluck('id')->search($this->member->id))
            ->and($rows->pluck('id')->search($this->admin->id))->toBeGreaterThan($rows->pluck('id')->search($this->member->id));
    });

    it('has no profile summary for an administrator without the member role', function () {
        $this->actingAs($this->admin)->getJson($this->tenantUrl("/api/admin/accounts/{$this->otherAdmin->id}"))
            ->assertJsonPath('data.profile_summary.visible', true);
        $this->actingAs($this->superAdmin)->getJson($this->tenantUrl("/api/admin/accounts/{$this->admin->id}"))
            ->assertJsonPath('data.profile_summary', null);
    });
});

describe('activation', function () {
    it('lets an administrator deactivate then reactivate a member and closes her sessions', function () {
        DB::table('sessions')->insert(['id' => 'session-aminata', 'user_id' => $this->member->id, 'payload' => '', 'last_activity' => time()]);

        $this->actingAs($this->admin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$this->member->id}/activation"))
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        expect($this->member->refresh()->is_active)->toBeFalse()
            ->and(DB::table('sessions')->where('user_id', $this->member->id)->exists())->toBeFalse();

        $this->actingAs($this->admin)->putJson($this->tenantUrl("/api/admin/accounts/{$this->member->id}/activation"))
            ->assertOk()
            ->assertJsonPath('data.status', 'active');
    });

    it('forbids an administrator to deactivate another administrator', function () {
        $this->actingAs($this->admin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$this->otherAdmin->id}/activation"))
            ->assertForbidden();

        $this->actingAs($this->superAdmin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$this->otherAdmin->id}/activation"))
            ->assertOk();
    });

    it('protects the superadmin account, even from the superadmin', function () {
        $this->actingAs($this->admin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$this->superAdmin->id}/activation"))->assertForbidden();
        $this->actingAs($this->superAdmin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$this->superAdmin->id}/activation"))->assertForbidden();
        $this->actingAs($this->superAdmin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$this->superAdmin->id}"))->assertForbidden();
        $this->actingAs($this->superAdmin)->putJson($this->tenantUrl("/api/admin/accounts/{$this->superAdmin->id}/roles"), ['roles' => ['admin']])->assertForbidden();

        expect($this->superAdmin->refresh()->is_active)->toBeTrue();
    });
});

describe('roles', function () {
    it('lets only the superadmin change roles', function () {
        $this->actingAs($this->admin)->putJson($this->tenantUrl("/api/admin/accounts/{$this->member->id}/roles"), ['roles' => ['admin', 'member']])
            ->assertForbidden();

        $this->actingAs($this->superAdmin)->putJson($this->tenantUrl("/api/admin/accounts/{$this->member->id}/roles"), ['roles' => ['admin', 'member']])
            ->assertOk()
            ->assertJsonPath('data.roles', ['admin', 'member']);
    });

    it('requires at least one role and never grants superadmin', function () {
        $this->actingAs($this->superAdmin)->putJson($this->tenantUrl("/api/admin/accounts/{$this->member->id}/roles"), ['roles' => []])
            ->assertJsonValidationErrors(['roles' => 'Choisissez au moins un rôle.']);

        $this->actingAs($this->superAdmin)->putJson($this->tenantUrl("/api/admin/accounts/{$this->member->id}/roles"), ['roles' => ['superadmin']])
            ->assertJsonValidationErrors(['roles.0']);
    });
});

describe('invitations', function () {
    it('invites a member, who appears as a pending account without password', function () {
        Notification::fake();

        $this->actingAs($this->admin)->postJson($this->tenantUrl('/api/admin/invitations'), [
            'first_name' => 'Mireille',
            'last_name' => 'Sawadogo',
            'email' => ' Mireille.Sawadogo@Example.org ',
            'roles' => ['admin'],
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.roles', ['member'])
            ->assertJsonPath('data.invited_by', 'Hélène Marchand')
            ->assertJsonPath('data.member_since', null);

        $account = User::where('email', 'mireille.sawadogo@example.org')->firstOrFail();
        expect($account->password)->toBeNull()
            ->and($account->is_active)->toBeFalse()
            ->and(DB::table('invitation_tokens')->where('email', $account->email)->exists())->toBeTrue();

        Notification::assertSentTo($account, InvitationNotification::class, fn (InvitationNotification $notification) => $notification->inviterName === 'Hélène Marchand'
            && $notification->expiresAt->isSameDay(now()->addDays(7)));
    });

    it('lets the superadmin invite an administrator', function () {
        Notification::fake();

        $this->actingAs($this->superAdmin)->postJson($this->tenantUrl('/api/admin/invitations'), [
            'first_name' => 'Sophie',
            'last_name' => 'Laurent',
            'email' => 'sophie@example.org',
            'roles' => ['admin'],
        ])->assertCreated()->assertJsonPath('data.roles', ['admin']);
    });

    it('refuses an email already used', function () {
        $this->actingAs($this->admin)->postJson($this->tenantUrl('/api/admin/invitations'), [
            'first_name' => 'Aminata',
            'last_name' => 'Diallo',
            'email' => 'AMINATA@example.org',
        ])->assertJsonValidationErrors(['email' => 'Cette adresse est déjà utilisée par un compte.']);
    });

    it('marks an invitation as expired after 7 days, and resending renews it', function () {
        Notification::fake();
        $account = invitedAccount(8);

        $this->actingAs($this->admin)->getJson($this->tenantUrl("/api/admin/accounts/{$account->id}"))
            ->assertJsonPath('data.status', 'expired');

        $this->actingAs($this->admin)->postJson($this->tenantUrl("/api/admin/accounts/{$account->id}/invitation"))
            ->assertOk()
            ->assertJsonPath('data.status', 'pending');

        Notification::assertSentTo($account, InvitationNotification::class);
    });

    it('refuses to resend an invitation less than 10 minutes after the last sending', function () {
        Notification::fake();
        $account = invitedAccount();
        $account->forceFill(['invited_at' => now()->subMinutes(4)])->save();

        $this->actingAs($this->admin)->getJson($this->tenantUrl("/api/admin/accounts/{$account->id}"))
            ->assertJsonPath('data.invitation_resendable_at', now()->addMinutes(6)->toIso8601String());
        $this->actingAs($this->admin)->postJson($this->tenantUrl("/api/admin/accounts/{$account->id}/invitation"))
            ->assertUnprocessable();
        Notification::assertNothingSent();

        $this->travel(7)->minutes();
        $this->actingAs($this->admin)->postJson($this->tenantUrl("/api/admin/accounts/{$account->id}/invitation"))
            ->assertOk();
        Notification::assertSentTo($account, InvitationNotification::class);
    });

    it('cancels an invitation, and an administrator cannot manage an administrator invitation', function () {
        $memberInvite = invitedAccount();
        $adminInvite = invitedAccount(1, Role::Admin);

        $this->actingAs($this->admin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$adminInvite->id}/invitation"))->assertForbidden();
        $this->actingAs($this->admin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$memberInvite->id}/invitation"))->assertNoContent();

        expect(User::find($memberInvite->id))->toBeNull();
    });

    it('cannot deactivate an invitation nor manage the invitation of an active account', function () {
        $account = invitedAccount();

        $this->actingAs($this->admin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$account->id}/activation"))->assertForbidden();
        $this->actingAs($this->admin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$this->member->id}/invitation"))->assertForbidden();
    });
});

describe('deletion', function () {
    it('lets only the superadmin delete an account', function () {
        $this->actingAs($this->admin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$this->member->id}"))->assertForbidden();
    });

    it('erases the account and keeps only the figures and the exchanged messages', function () {
        Storage::fake('local');
        Storage::disk('local')->put('members/aminata.webp', 'photo');
        MemberProfile::factory()->for($this->member)->create(['photo_path' => 'members/aminata.webp']);
        $event = Event::factory()->create();
        $event->participants()->attach($this->member->id);
        $this->otherAdmin->favorites()->attach($this->member->id);

        $conversation = app(Messaging::class)->conversationBetween($this->otherAdmin, $this->member);
        app(Messaging::class)->send($conversation, $this->member, 'Bonjour Claire');

        $this->actingAs($this->superAdmin)->deleteJson($this->tenantUrl("/api/admin/accounts/{$this->member->id}"))->assertNoContent();

        expect(User::find($this->member->id))->toBeNull()
            ->and(MemberProfile::where('user_id', $this->member->id)->exists())->toBeFalse()
            ->and($event->refresh()->former_attendees_count)->toBe(1)
            ->and($event->participants()->count())->toBe(0)
            ->and($this->otherAdmin->favorites()->count())->toBe(0)
            ->and($this->otherAdmin->notifications()->count())->toBe(0)
            ->and($conversation->messages()->sole()->user_id)->toBeNull()
            ->and($conversation->messages()->sole()->body)->toBe('Bonjour Claire');
        Storage::disk('local')->assertMissing('members/aminata.webp');
    });
});
