<?php

use App\Models\AdoptionApplication;
use App\Models\Animal;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->create(['organization_id' => $this->organization->id, 'is_organization_admin' => true]);
    $this->worker = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->animal = Animal::factory()->create(['organization_id' => $this->organization->id, 'status' => 'ready']);
    $this->payload = ['candidate_name' => 'Anna Nowak', 'email' => 'anna@example.com', 'phone' => '', 'housing' => 'Mieszkanie, zgoda domowników.', 'coordinator_id' => $this->worker->id, 'status' => 'new'];
});

test('worker creates a private adoption application with a coordinator task and history', function () {
    $this->actingAs($this->worker)->post(route('animals.adoptions.store', $this->animal), [...$this->payload, 'animal_id' => 999, 'task_title' => 'Umówić spotkanie', 'task_due_date' => today()->addDay()->toDateString(), 'attachments' => [UploadedFile::fake()->image('ankieta.png')]])->assertSessionHasNoErrors()->assertRedirect();
    $application = AdoptionApplication::sole();
    expect($application->animal_id)->toBe($this->animal->id)->and($application->history()->sole()->author_name)->toBe($this->worker->name)->and($application->tasks()->sole()->assignee_id)->toBe($this->worker->id);
    $attachment = $application->attachments()->sole();
    $this->get(route('adoptions.show', $application))->assertInertia(fn (Assert $page) => $page->component('adoptions/Show')->has('history.data', 1)->has('tasks.data', 1)->missing('application.attachments.0.path'));
    $this->get(route('adoptions.download', $attachment))->assertDownload('ankieta.png');
});

test('administrator completes the process and competing applications are closed atomically', function () {
    $this->actingAs($this->worker)->post(route('animals.adoptions.store', $this->animal), $this->payload)->assertRedirect();
    $application = AdoptionApplication::sole();
    $other = AdoptionApplication::factory()->create(['animal_id' => $this->animal->id]);
    foreach (['reviewing', 'meeting'] as $offset => $status) {
        $this->put(route('adoptions.update', $application), [...$this->payload, 'version' => $offset + 1, 'status' => $status])->assertSessionHasNoErrors()->assertRedirect();
    }
    $this->actingAs($this->admin)->put(route('adoptions.update', $application), [...$this->payload, 'version' => 3, 'status' => 'approved', 'note' => 'Pozytywna decyzja po spotkaniu.'])->assertSessionHasNoErrors()->assertRedirect();
    $this->post(route('adoptions.update', $application), [...$this->payload, '_method' => 'put', 'version' => 4, 'status' => 'completed', 'note' => 'Wydano zwierzę.', 'adopted_on' => today()->toDateString(), 'attachments' => [UploadedFile::fake()->image('umowa.png')]])->assertSessionHasNoErrors()->assertRedirect();
    expect($application->fresh()->status)->toBe('completed')->and($this->animal->fresh()->status)->toBe('adopted')->and($other->fresh()->status)->toBe('withdrawn')->and($application->history()->count())->toBe(5);
    expect($other->history()->sole()->note)->toContain('automatycznie');
    $this->assertDatabaseHas('animal_status_histories', ['animal_id' => $this->animal->id, 'from_status' => 'ready', 'to_status' => 'adopted']);
    $this->put(route('adoptions.update', $other), [...$this->payload, 'version' => 1, 'status' => 'reviewing'])->assertSessionHasErrors('version');
    $this->post(route('animals.adoptions.store', $this->animal), $this->payload)->assertSessionHasErrors('status');
});

test('workers cannot approve reject finalize or alter approved applications', function (string $initial, string $target) {
    $application = AdoptionApplication::factory()->create(['animal_id' => $this->animal->id, 'status' => $initial]);
    $this->actingAs($this->worker)->put(route('adoptions.update', $application), [...$this->payload, 'version' => 1, 'status' => $target, 'note' => 'Decyzja', 'adopted_on' => today()->toDateString()])->assertForbidden();
    expect($application->fresh()->status)->toBe($initial)->and($application->history()->count())->toBe(0);
})->with([['meeting', 'approved'], ['new', 'rejected'], ['approved', 'completed'], ['approved', 'approved']]);

test('finalization requires available animal a document and a valid release date', function () {
    $application = AdoptionApplication::factory()->create(['animal_id' => $this->animal->id, 'status' => 'approved']);
    $payload = [...$this->payload, 'version' => 1, 'status' => 'completed', 'note' => 'Wydanie', 'adopted_on' => today()->toDateString()];
    $this->actingAs($this->admin)->put(route('adoptions.update', $application), $payload)->assertSessionHasErrors('attachments');
    $this->put(route('adoptions.update', $application), [...$payload, 'adopted_on' => today()->subYear()->toDateString(), 'attachments' => [UploadedFile::fake()->image('umowa.png')]])->assertSessionHasErrors('adopted_on');
    $this->animal->update(['status' => 'treatment']);
    $this->put(route('adoptions.update', $application), [...$payload, 'attachments' => [UploadedFile::fake()->image('umowa.png')]])->assertSessionHasErrors('status');
    expect($application->fresh()->status)->toBe('approved')->and($application->attachments()->count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('finished applications cannot be edited or duplicated even after animal status is manually changed', function () {
    $completed = AdoptionApplication::factory()->create(['animal_id' => $this->animal->id, 'status' => 'completed']);
    $second = AdoptionApplication::factory()->create(['animal_id' => $this->animal->id, 'status' => 'approved']);
    $this->actingAs($this->admin)->put(route('adoptions.update', $completed), [...$this->payload, 'version' => 1])->assertSessionHasErrors('status');
    $this->put(route('adoptions.update', $second), [...$this->payload, 'version' => 1, 'status' => 'completed', 'note' => 'Druga adopcja', 'adopted_on' => today()->toDateString()])->assertSessionHasErrors('status');
    expect(AdoptionApplication::where('status', 'completed')->count())->toBe(1);
});

test('tenant isolation covers lists applications documents and write operations', function () {
    $foreign = AdoptionApplication::factory()->create();
    $document = $foreign->attachments()->create(['path' => 'secret.pdf', 'name' => 'secret.pdf']);
    $this->actingAs($this->worker)->get(route('adoptions.show', $foreign))->assertNotFound();
    $this->get(route('animals.adoptions.index', $foreign->animal))->assertNotFound();
    $this->get(route('adoptions.download', $document))->assertNotFound();
    $this->put(route('adoptions.update', $foreign), [...$this->payload, 'version' => 1])->assertNotFound();
    $this->post(route('animals.adoptions.store', $foreign->animal), $this->payload)->assertNotFound();
    $this->get(route('adoptions.index'))->assertInertia(fn (Assert $page) => $page->has('applications.data', 0));
});

test('invalid coordinator and missing contact information are rejected on the correct page', function () {
    $foreign = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);
    $this->actingAs($this->worker)->withSession(['_previous.url' => route('animals.photo', $this->animal)])->post(route('animals.adoptions.store', $this->animal), [...$this->payload, 'email' => '', 'phone' => '', 'coordinator_id' => $foreign->id])->assertSessionHasErrors(['email', 'phone', 'coordinator_id'])->assertRedirect(route('animals.adoptions.index', $this->animal));
    $this->assertDatabaseCount('adoption_applications', 0);
});

test('stage skipping stale edits and unreasoned decisions cannot overwrite history', function () {
    $application = AdoptionApplication::factory()->create(['animal_id' => $this->animal->id]);
    $this->actingAs($this->admin)->put(route('adoptions.update', $application), [...$this->payload, 'version' => 1, 'status' => 'approved', 'note' => 'Pominięcie etapów'])->assertSessionHasErrors('status');
    $this->put(route('adoptions.update', $application), [...$this->payload, 'version' => 1, 'status' => 'rejected'])->assertSessionHasErrors('note');
    $this->put(route('adoptions.update', $application), [...$this->payload, 'version' => 1, 'status' => 'reviewing'])->assertSessionHasNoErrors()->assertRedirect();
    $this->put(route('adoptions.update', $application), [...$this->payload, 'version' => 1, 'candidate_name' => 'Nadpisanie'])->assertSessionHasErrors('version');
    expect($application->fresh()->candidate_name)->toBe('Anna Nowak')->and($application->history()->count())->toBe(1);
});

test('failed save rolls back both uploaded documents and database changes', function () {
    $application = AdoptionApplication::factory()->create(['animal_id' => $this->animal->id]);
    $this->actingAs($this->admin)->put(route('adoptions.update', $application), [...$this->payload, 'version' => 1, 'status' => 'withdrawn', 'note' => 'Rezygnacja', 'task_title' => 'Niedozwolone nowe zadanie', 'task_due_date' => today()->toDateString(), 'attachments' => [UploadedFile::fake()->image('plik.png')]])->assertSessionHasErrors('task_title');
    expect($application->fresh()->status)->toBe('new')->and($application->history()->count())->toBe(0)->and($application->attachments()->count())->toBe(0)->and(Storage::disk('local')->allFiles())->toBe([]);
});

test('guests and platform administrators cannot browse tenant adoption data', function () {
    $this->get(route('adoptions.index'))->assertRedirect(route('login'));
    $platform = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($platform)->get(route('adoptions.index'))->assertForbidden();
});

test('database enforces a single completed adoption for an animal', function () {
    AdoptionApplication::factory()->create(['animal_id' => $this->animal->id, 'status' => 'completed']);
    expect(fn () => AdoptionApplication::factory()->create(['animal_id' => $this->animal->id, 'status' => 'completed']))->toThrow(QueryException::class);
});

test('withdrawal preserves history and closed applications leave the open list', function () {
    $this->actingAs($this->worker)->post(route('animals.adoptions.store', $this->animal), $this->payload)->assertRedirect();
    $application = AdoptionApplication::sole();
    $this->put(route('adoptions.update', $application), [...$this->payload, 'version' => 1, 'status' => 'withdrawn', 'note' => 'Kandydat zrezygnował.'])->assertSessionHasNoErrors()->assertRedirect();
    $this->get(route('adoptions.index'))->assertInertia(fn (Assert $page) => $page->has('applications.data', 0));
    $this->get(route('adoptions.index', ['status' => 'withdrawn']))->assertInertia(fn (Assert $page) => $page->has('applications.data', 1));
    expect($application->history()->count())->toBe(2)->and($this->animal->fresh()->status)->toBe('ready');
});

test('inactive coordinators and invalid document formats are rejected', function () {
    $this->worker->forceFill(['is_active' => false])->save();
    $this->actingAs($this->admin)->post(route('animals.adoptions.store', $this->animal), [...$this->payload, 'attachments' => [UploadedFile::fake()->create('script.html', 1, 'text/html')]])->assertSessionHasErrors(['coordinator_id', 'attachments.0']);
    $this->assertDatabaseCount('adoption_applications', 0);
});
