<?php

use App\Models\Organization;
use App\Models\User;
use App\Notifications\TeamInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->create(['organization_id' => $this->organization->id, 'is_organization_admin' => true, 'organization_role' => 'admin']);
});

test('administrator invites a worker without exposing the invitation token in the team list', function () {
    $this->actingAs($this->admin)->post(route('team.store'), ['name' => 'Anna Nowak', 'email' => 'ANNA@example.com', 'role' => 'veterinarian'])->assertRedirect(route('team.index'));
    $worker = User::where('email', 'anna@example.com')->firstOrFail();
    expect($worker->organization_id)->toBe($this->organization->id)->and($worker->is_active)->toBeFalse()->and($worker->teamRole())->toBe('veterinarian');
    Notification::assertSentTo($worker, TeamInvitation::class, function ($notification) use ($worker) {
        return hash('sha256', $notification->token) === $worker->invitation_token && $notification->shouldSend($worker, 'mail');
    });
    $this->get(route('team.index'))->assertInertia(fn (Assert $page) => $page->component('team/Index')->has('members.data', 2)->missing('members.data.0.invitation_token')->missing('members.data.1.invitation_token'));
});

test('only tenant administrators can manage the team', function (array $attributes) {
    $worker = User::factory()->create(array_merge(['organization_id' => $this->organization->id], $attributes));
    $this->actingAs($worker)->get(route('team.index'))->assertForbidden();
    $this->post(route('team.store'), ['name' => 'Test', 'email' => 'test@example.com', 'role' => 'admin'])->assertForbidden();
    Notification::assertNothingSent();
})->with([
    'caretaker' => [['organization_role' => 'caretaker']],
    'veterinarian' => [['organization_role' => 'veterinarian']],
    'platform administrator' => [['is_super_admin' => true]],
]);

test('foreign organization members cannot be changed or invited again', function () {
    $foreign = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);
    $this->actingAs($this->admin)->put(route('team.update', $foreign->id), ['role' => 'admin', 'is_active' => false])->assertNotFound();
    $this->post(route('team.resend', $foreign->id))->assertNotFound();
    $this->delete(route('team.cancel', $foreign->id))->assertNotFound();
    $this->get(route('team.index'))->assertInertia(fn (Assert $page) => $page->has('members.data', 1)->where('members.data.0.id', $this->admin->id));
    expect($foreign->fresh()->is_active)->toBeTrue();
});

test('invitation activates only its own account and cannot be reused', function () {
    $token = str_repeat('a', 64);
    $worker = User::factory()->create(['organization_id' => $this->organization->id, 'is_active' => false, 'invited_at' => now(), 'invitation_expires_at' => now()->addDays(7), 'invitation_token' => hash('sha256', $token)]);
    $this->get(route('invitations.show', $token))->assertOk()->assertHeader('Referrer-Policy', 'no-referrer');
    $data = ['name' => 'Anna', 'password' => 'BezpieczneHaslo123', 'password_confirmation' => 'BezpieczneHaslo123', 'is_super_admin' => true, 'role' => 'admin', 'organization_id' => 999];
    $this->post(route('invitations.store', $token), $data)->assertRedirect(route('login'));
    $worker->refresh();
    expect($worker->is_active)->toBeTrue()->and($worker->email_verified_at)->not->toBeNull()->and($worker->invitation_token)->toBeNull()->and($worker->is_super_admin)->toBeFalse()->and($worker->teamRole())->toBe('caretaker')->and($worker->organization_id)->toBe($this->organization->id)->and(Hash::check($data['password'], $worker->password))->toBeTrue();
    $this->post(route('invitations.store', $token), $data)->assertNotFound();
    $this->get(route('invitations.show', $token))->assertStatus(410);
});

test('expired invitations cannot activate accounts', function () {
    $token = str_repeat('b', 64);
    $worker = User::factory()->create(['organization_id' => $this->organization->id, 'is_active' => false, 'invited_at' => now()->subDays(8), 'invitation_expires_at' => now()->subDay(), 'invitation_token' => hash('sha256', $token)]);
    $this->post(route('invitations.store', $token), ['name' => 'Anna', 'password' => 'BezpieczneHaslo123', 'password_confirmation' => 'BezpieczneHaslo123'])->assertStatus(410);
    expect($worker->fresh()->is_active)->toBeFalse();
});

test('resending and cancelling invalidate earlier invitations', function () {
    $oldToken = str_repeat('c', 64);
    $worker = User::factory()->create(['organization_id' => $this->organization->id, 'is_active' => false, 'invited_at' => now(), 'invitation_expires_at' => now()->addDays(7), 'invitation_token' => hash('sha256', $oldToken)]);
    $this->actingAs($this->admin)->post(route('team.resend', $worker->id))->assertRedirect();
    expect((new TeamInvitation($oldToken))->shouldSend($worker, 'mail'))->toBeFalse();
    $notification = Notification::sent($worker, TeamInvitation::class)->first();
    expect($notification->shouldSend($worker, 'mail'))->toBeTrue();
    $this->delete(route('team.cancel', $worker->id))->assertRedirect();
    expect($notification->shouldSend($worker, 'mail'))->toBeFalse()->and($worker->fresh()->is_active)->toBeFalse();
});

test('administrator cannot block or demote their own account or activate pending invitations', function () {
    $this->actingAs($this->admin)->put(route('team.update', $this->admin->id), ['role' => 'caretaker', 'is_active' => false])->assertSessionHasErrors('member');
    expect($this->admin->fresh()->is_active)->toBeTrue()->and($this->admin->fresh()->is_organization_admin)->toBeTrue();
    $worker = User::factory()->create(['organization_id' => $this->organization->id, 'is_active' => false, 'invited_at' => now()]);
    $this->put(route('team.update', $worker->id), ['role' => 'admin', 'is_active' => true])->assertSessionHasErrors('member');
    expect($worker->fresh()->is_active)->toBeFalse();
});

test('blocked accounts lose existing sessions and cannot log in', function () {
    $worker = User::factory()->create(['organization_id' => $this->organization->id, 'password' => 'BezpieczneHaslo123']);
    $remember = $worker->remember_token;
    $this->actingAs($this->admin)->put(route('team.update', $worker->id), ['role' => 'veterinarian', 'is_active' => false])->assertRedirect();
    expect($worker->fresh()->remember_token)->not->toBe($remember);
    $this->actingAs($worker->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    $this->assertGuest();
    $this->post(route('login.store'), ['email' => $worker->email, 'password' => 'BezpieczneHaslo123'])->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->actingAs($this->admin)->put(route('team.update', $worker->id), ['role' => 'caretaker', 'is_active' => true])->assertRedirect();
    expect($worker->fresh()->is_active)->toBeTrue();
});

test('existing email accounts cannot be taken over through invitations', function () {
    $other = User::factory()->create();
    $this->actingAs($this->admin)->post(route('team.store'), ['name' => 'Test', 'email' => $other->email, 'role' => 'admin'])->assertSessionHasErrors('email');
    expect($other->fresh()->organization_id)->toBeNull();
    Notification::assertNothingSent();
});

test('an administrator must transfer their role before deleting their account', function () {
    $this->actingAs($this->admin)->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('password');
    $this->assertModelExists($this->admin);
});

test('activated caretaker can log in and open the dashboard and animals', function () {
    $token = str_repeat('d', 64);
    $worker = User::factory()->create(['organization_id' => $this->organization->id, 'is_active' => false, 'invited_at' => now(), 'invitation_expires_at' => now()->addDays(7), 'invitation_token' => hash('sha256', $token)]);
    $this->post(route('invitations.store', $token), ['name' => 'Opiekun', 'password' => 'BezpieczneHaslo123', 'password_confirmation' => 'BezpieczneHaslo123'])->assertRedirect(route('login'));
    $this->post(route('login.store'), ['email' => $worker->email, 'password' => 'BezpieczneHaslo123'])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($worker);
    $this->get(route('dashboard'))->assertOk();
    $this->get(route('animals.index'))->assertOk();
    $this->get(route('team.index'))->assertForbidden();
});
