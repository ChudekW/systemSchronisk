<?php

use App\Models\Animal;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function animalPayload(array $overrides = []): array
{
    return [...[
        'name' => 'Luna', 'registration_number' => 'P/2026/001', 'species' => 'dog', 'sex' => 'female',
        'admitted_at' => '2026-01-10', 'birth_date' => '2024-01-01', 'birth_date_estimated' => true,
        'status' => 'quarantine', 'admission_reason' => 'found', 'sterilization_status' => 'unknown',
    ], ...$overrides];
}

test('a shelter member creates an animal in their shelter ignoring forged ownership', function () {
    $organization = Organization::factory()->create();
    $other = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);

    $this->actingAs($user)->post(route('animals.store'), animalPayload(['organization_id' => $other->id, 'photo_path' => 'forged.jpg']))
        ->assertSessionHasNoErrors()->assertRedirect();

    $animal = Animal::sole();
    $this->assertDatabaseHas('animals', ['id' => $animal->id, 'name' => 'Luna', 'organization_id' => $organization->id, 'photo_path' => null]);
    $this->assertDatabaseHas('animal_status_histories', ['animal_id' => $animal->id, 'user_id' => $user->id, 'from_status' => null, 'to_status' => 'quarantine']);
});

test('animal list search and filters remain inside the current shelter', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create(['organization_id' => $organization->id]);
    $match = Animal::factory()->create(['organization_id' => $organization->id, 'name' => 'Luna', 'status' => 'ready']);
    Animal::factory()->create(['organization_id' => $organization->id, 'name' => 'Luna', 'status' => 'treatment']);
    Animal::factory()->create(['organization_id' => $organization->id, 'name' => 'Luna', 'status' => 'ready', 'species' => 'cat']);
    Animal::factory()->create(['name' => 'Luna', 'status' => 'ready']);

    $this->actingAs($user)->get(route('animals.index', ['search' => 'luna', 'status' => 'ready', 'species' => 'dog']))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->component('animals/Index')->has('animals.data', 1)->where('animals.data.0.id', $match->id));
});

test('shelter members can open create edit and detail pages', function () {
    $animal = Animal::factory()->create();
    $user = User::factory()->create(['organization_id' => $animal->organization_id]);

    $this->actingAs($user)->get(route('animals.create'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('animals/Form')->where('animal', null));
    $this->get(route('animals.edit', $animal))->assertOk()->assertInertia(fn (Assert $page) => $page->component('animals/Form')->where('animal.id', $animal->id));
    $this->get(route('animals.show', $animal))->assertOk()->assertInertia(fn (Assert $page) => $page->component('animals/Show')->where('animal.id', $animal->id)->missing('animal.photo_path'));
});

test('foreign animal details edits and photos are not exposed', function () {
    $animal = Animal::factory()->create(['name' => 'Other']);
    $user = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);

    foreach (['show', 'edit', 'photo'] as $action) {
        $this->actingAs($user)->get(route('animals.'.$action, $animal))->assertNotFound();
    }
    $this->put(route('animals.update', $animal), animalPayload())->assertNotFound();

    $this->assertDatabaseHas('animals', ['id' => $animal->id, 'name' => 'Other']);
    $this->assertDatabaseCount('animal_status_histories', 0);
});

test('animal permissions require an active tenant and never imply platform access', function () {
    $active = Organization::factory()->create();
    $inactive = Organization::factory()->create(['is_active' => false]);
    foreach ([
        [User::factory()->create(['organization_id' => $active->id]), true],
        [User::factory()->create(['organization_id' => $active->id, 'is_organization_admin' => true]), true],
        [User::factory()->create(['organization_id' => $inactive->id]), false],
        [User::factory()->create(), false],
        [User::factory()->create(['is_super_admin' => true]), false],
    ] as [$user, $allowed]) {
        expect(Gate::forUser($user)->allows('manage-animals'))->toBe($allowed);
    }
});

test('inactive shelter users cannot read or write animal records', function () {
    $animal = Animal::factory()->create(['organization_id' => Organization::factory()->create(['is_active' => false])->id]);
    $user = User::factory()->create(['organization_id' => $animal->organization_id]);

    $this->actingAs($user)->get(route('animals.index'))->assertForbidden();
    $this->get(route('animals.photo', $animal))->assertForbidden();
    $this->post(route('animals.store'), animalPayload())->assertForbidden();
    $this->put(route('animals.update', $animal), animalPayload())->assertForbidden();
    $this->assertDatabaseCount('animals', 1);
});

test('guests and unverified members cannot access the animal registry', function () {
    $this->get(route('animals.index'))->assertRedirect(route('login'));
    $user = User::factory()->unverified()->create(['organization_id' => Organization::factory()->create()->id]);
    $this->actingAs($user)->get(route('animals.index'))->assertRedirect(route('verification.notice'));
});

test('editing an animal records each status change once and preserves ownership', function () {
    $animal = Animal::factory()->create(['status' => 'admitted']);
    $user = User::factory()->create(['organization_id' => $animal->organization_id]);
    $other = Organization::factory()->create();
    $payload = animalPayload(['status' => 'ready', 'status_note' => 'Zakończono kwarantannę.', 'organization_id' => $other->id]);

    $this->actingAs($user)->put(route('animals.update', $animal), $payload)->assertSessionHasNoErrors()->assertRedirect(route('animals.show', $animal));
    $this->put(route('animals.update', $animal), $payload)->assertSessionHasNoErrors();

    $this->assertDatabaseHas('animals', ['id' => $animal->id, 'status' => 'ready', 'organization_id' => $user->organization_id]);
    $this->assertDatabaseHas('animal_status_histories', ['animal_id' => $animal->id, 'user_id' => $user->id, 'from_status' => 'admitted', 'to_status' => 'ready', 'note' => 'Zakończono kwarantannę.']);
    $this->assertDatabaseCount('animal_status_histories', 1);
});

test('registration numbers and microchips are unique within a shelter only', function () {
    $user = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);
    Animal::factory()->create(['registration_number' => 'P/2026/001', 'microchip' => '123456789012345']);
    $payload = animalPayload(['microchip' => '123456789012345']);

    $this->actingAs($user)->post(route('animals.store'), $payload)->assertSessionHasNoErrors();
    $this->post(route('animals.store'), $payload)->assertSessionHasErrors(['registration_number', 'microchip']);

    $this->assertDatabaseCount('animals', 2);
});

test('invalid animal data does not create a record', function (array $changes, string $field) {
    $user = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);

    $this->actingAs($user)->post(route('animals.store'), animalPayload($changes))->assertSessionHasErrors($field);

    $this->assertDatabaseCount('animals', 0);
    $this->assertDatabaseCount('animal_status_histories', 0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'unknown species' => [['species' => 'invalid'], 'species'],
    'invalid status' => [['status' => 'invalid'], 'status'],
    'future admission' => [['admitted_at' => '2999-01-01'], 'admitted_at'],
    'birth after admission' => [['birth_date' => '2026-02-01'], 'birth_date'],
    'negative weight' => [['weight_kg' => -2], 'weight_kg'],
    'malformed chip' => [['microchip' => 'not-a-chip'], 'microchip'],
]);

test('photos are private and can be replaced or removed', function () {
    Storage::fake('local');
    $user = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);
    $this->actingAs($user)->post(route('animals.store'), animalPayload(['photo' => UploadedFile::fake()->image('luna.jpg')]))->assertSessionHasNoErrors();
    $animal = Animal::sole();
    $oldPath = $animal->photo_path;
    Storage::disk('local')->assertExists($oldPath);
    $this->get(route('animals.photo', $animal))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

    $this->post(route('animals.update', $animal), animalPayload(['_method' => 'PUT', 'photo' => UploadedFile::fake()->image('new.jpg')]))->assertSessionHasNoErrors();
    $newPath = $animal->fresh()->photo_path;
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($newPath);

    $this->put(route('animals.update', $animal), animalPayload(['remove_photo' => true]))->assertSessionHasNoErrors();
    Storage::disk('local')->assertMissing($newPath);
    $this->get(route('animals.photo', $animal))->assertNotFound();
});

test('non image uploads are rejected without storing files', function () {
    Storage::fake('local');
    $user = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);

    $this->actingAs($user)->post(route('animals.store'), animalPayload(['photo' => UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml')]))->assertSessionHasErrors('photo');

    expect(Storage::disk('local')->allFiles())->toBeEmpty();
    $this->assertDatabaseCount('animals', 0);
});
