<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function organizationPayload(): array
{
    return [
        'name' => 'Fundacja Łapa', 'shelter_name' => 'Schronisko Łapa',
        'admin_name' => 'Anna Kowalska', 'admin_email' => 'anna@example.test',
        'admin_password' => 'SecurePassword123!', 'admin_password_confirmation' => 'SecurePassword123!',
    ];
}

test('platform administrator creates an organization with an isolated administrator account', function () {
    $super = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($super)->post(route('organizations.store'), organizationPayload())->assertSessionHasNoErrors()->assertRedirect();
    $organization = Organization::sole();
    $admin = User::where('email', 'anna@example.test')->sole();
    expect($admin->organization_id)->toBe($organization->id)
        ->and($admin->is_organization_admin)->toBeTrue()
        ->and($admin->is_super_admin)->toBeFalse()
        ->and($admin->email_verified_at)->toBeNull()
        ->and(Hash::check('SecurePassword123!', $admin->password))->toBeTrue();
});

test('ordinary and tenant users cannot manage platform organizations', function () {
    $organization = Organization::factory()->create();
    foreach ([User::factory()->create(), User::factory()->create(['organization_id' => $organization->id, 'is_organization_admin' => true])] as $user) {
        $this->actingAs($user)->get(route('organizations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('organizations.edit', $organization))->assertForbidden();
        $this->actingAs($user)->post(route('organizations.store'), organizationPayload())->assertForbidden();
        $this->actingAs($user)->put(route('organizations.update', $organization), ['name' => 'Changed'])->assertForbidden();
    }
    expect(Organization::count())->toBe(1);
});

test('tenant can only read and update its own organization and cannot change privilege fields', function () {
    $own = Organization::factory()->create();
    $other = Organization::factory()->create();
    $otherUser = User::factory()->create(['organization_id' => $other->id]);
    $admin = User::factory()->create(['organization_id' => $own->id, 'is_organization_admin' => true]);
    $this->actingAs($admin)->get(route('tenant.organization.edit'))->assertInertia(fn (Assert $page) => $page
        ->component('organizations/Edit')->where('organization.id', $own->id)->has('members', 1)->where('members.0.id', $admin->id));
    $this->put(route('tenant.organization.update'), [
        'name' => 'Moja fundacja', 'shelter_name' => 'Mój azyl',
        'organization_id' => $other->id, 'is_active' => false, 'is_super_admin' => true,
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($own->fresh()->name)->toBe('Moja fundacja')
        ->and($own->fresh()->is_active)->toBeTrue()
        ->and($other->fresh()->name)->toBe($other->name)
        ->and($admin->fresh()->is_super_admin)->toBeFalse();
});

test('inactive tenants and non administrators cannot access organization management', function () {
    $inactive = Organization::factory()->create(['is_active' => false]);
    $active = Organization::factory()->create();
    foreach ([
        User::factory()->create(['organization_id' => $inactive->id, 'is_organization_admin' => true]),
        User::factory()->create(['organization_id' => $active->id]),
        User::factory()->create(),
    ] as $user) {
        $this->actingAs($user)->get(route('tenant.organization.edit'))->assertForbidden();
        $this->put(route('tenant.organization.update'), ['name' => 'Test', 'shelter_name' => 'Test'])->assertForbidden();
        expect(Gate::forUser($user)->allows('manage-own-organization'))->toBeFalse();
    }
});

test('superadministrator can deactivate an organization', function () {
    $organization = Organization::factory()->create();
    $super = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($super)->put(route('organizations.update', $organization), [
        'name' => $organization->name, 'shelter_name' => $organization->shelter_name, 'is_active' => false,
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($organization->fresh()->is_active)->toBeFalse();
});

test('existing email and invalid password cannot create a partial organization', function () {
    $super = User::factory()->create(['is_super_admin' => true]);
    User::factory()->create(['email' => 'anna@example.test']);
    $this->actingAs($super)->post(route('organizations.store'), organizationPayload())->assertSessionHasErrors('admin_email');
    $this->post(route('organizations.store'), [...organizationPayload(), 'admin_email' => 'another@example.test', 'admin_password_confirmation' => 'different'])->assertSessionHasErrors('admin_password');
    $this->post(route('organizations.store'), [])->assertSessionHasErrors(['name', 'shelter_name', 'admin_name', 'admin_email', 'admin_password']);
    expect(Organization::count())->toBe(0);
});

test('platform privilege is explicit and cannot be obtained by registering the configured email', function () {
    $user = User::factory()->create(['email' => 'admin@dev10.pro']);
    expect(Gate::forUser($user)->allows('manage-organizations'))->toBeFalse();
    $this->artisan('app:promote-platform-administrator', ['email' => 'admin@dev10.pro'])->assertSuccessful();
    expect(Gate::forUser($user->fresh())->allows('manage-organizations'))->toBeTrue();
});

test('guests and unverified users cannot enter organization administration', function () {
    $this->get(route('organizations.index'))->assertRedirect(route('login'));
    $user = User::factory()->unverified()->create(['is_super_admin' => true]);
    $this->actingAs($user)->get(route('organizations.index'))->assertRedirect(route('verification.notice'));
});
