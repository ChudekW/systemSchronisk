<?php

use App\Models\Animal;
use App\Models\CareTask;
use App\Models\MedicalRecord;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->organization = Organization::factory()->create();
    $this->vet = User::factory()->create(['organization_id' => $this->organization->id, 'organization_role' => 'veterinarian']);
    $this->carer = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->animal = Animal::factory()->create(['organization_id' => $this->organization->id]);
    $this->payload = ['kind' => 'visit', 'title' => 'Badanie kontrolne', 'occurred_on' => today()->toDateString(), 'performed_by' => 'Anna Kowalska', 'description' => 'Opis badania.', 'recommendations' => 'Obserwować apetyt.'];
});

test('veterinarian records a visit and a follow up task atomically with private attachments', function () {
    $this->actingAs($this->vet)->post(route('animals.health.store', $this->animal), [...$this->payload, 'next_due_date' => today()->addWeek()->toDateString(), 'assignee_id' => $this->carer->id, 'attachments' => [UploadedFile::fake()->image('wyniki.png')], 'user_id' => $this->carer->id, 'author_name' => 'Fałszywy autor', 'animal_id' => 999])->assertSessionHasNoErrors()->assertRedirect(route('animals.health.index', $this->animal));
    $record = MedicalRecord::sole();
    expect($record->user_id)->toBe($this->vet->id)->and($record->animal_id)->toBe($this->animal->id)->and($record->author_name)->toBe($this->vet->name);
    $task = CareTask::sole();
    expect($record->care_task_id)->toBe($task->id)->and($task->assignee_id)->toBe($this->carer->id)->and($task->description)->toBe('Obserwować apetyt.')->and($task->history()->count())->toBe(1);
    $attachment = $record->attachments()->sole();
    Storage::disk('local')->assertExists($attachment->path);
    $this->get(route('animals.health.index', $this->animal))->assertInertia(fn (Assert $page) => $page->component('animals/Health')->where('canManageMedical', true)->has('records.data', 1)->missing('records.data.0.attachments.0.path')->where('records.data.0.care_task.id', $task->id));
    $this->actingAs($this->carer)->get(route('animals.health.download', [$this->animal, $attachment]))->assertDownload('wyniki.png')->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('caretaker can read recommendations and add observations but cannot write clinical records', function () {
    $record = MedicalRecord::factory()->create(['animal_id' => $this->animal->id, 'recommendations' => 'Spokojne spacery.']);
    $this->actingAs($this->carer)->get(route('animals.health.index', $this->animal))->assertInertia(fn (Assert $page) => $page->where('canManageMedical', false)->where('records.data.0.recommendations', 'Spokojne spacery.'));
    $this->post(route('animals.health.store', $this->animal), $this->payload)->assertForbidden();
    $observation = ['kind' => 'observation', 'title' => 'Apetyt', 'occurred_on' => today()->toDateString(), 'description' => 'Zjadł całą porcję.'];
    $this->post(route('animals.health.store', $this->animal), [...$observation, 'recommendations' => 'Nieuprawnione zalecenie'])->assertSessionHasErrors('recommendations');
    $this->post(route('animals.health.store', $this->animal), $observation)->assertSessionHasNoErrors()->assertRedirect();
    expect(MedicalRecord::count())->toBe(2)->and($record->fresh()->recommendations)->toBe('Spokojne spacery.');
});

test('shelter administrator can record clinical care', function () {
    $admin = User::factory()->create(['organization_id' => $this->organization->id, 'is_organization_admin' => true]);
    $this->actingAs($admin)->post(route('animals.health.store', $this->animal), $this->payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseCount('medical_records', 1);
});

test('corrections append a new record without changing the original or its task', function () {
    $this->actingAs($this->vet)->post(route('animals.health.store', $this->animal), [...$this->payload, 'next_due_date' => today()->addWeek()->toDateString(), 'assignee_id' => $this->carer->id])->assertRedirect();
    $original = MedicalRecord::sole();
    $this->post(route('animals.health.store', $this->animal), [...$this->payload, 'description' => 'Poprawiony opis.', 'correction_of_id' => $original->id])->assertSessionHasErrors('correction_reason');
    $this->post(route('animals.health.store', $this->animal), [...$this->payload, 'description' => 'Poprawiony opis.', 'correction_of_id' => $original->id, 'correction_reason' => 'Uzupełnienie wyniku badania.'])->assertSessionHasNoErrors()->assertRedirect();
    expect($original->fresh()->description)->toBe('Opis badania.')->and($original->corrections()->sole()->description)->toBe('Poprawiony opis.')->and(CareTask::count())->toBe(1);
    $this->put(route('animals.health.index', $this->animal).'/'.$original->id, $this->payload)->assertNotFound();
});

test('caretaker can correct only their own observations', function () {
    $own = MedicalRecord::factory()->create(['animal_id' => $this->animal->id, 'user_id' => $this->carer->id, 'kind' => 'observation']);
    $other = MedicalRecord::factory()->create(['animal_id' => $this->animal->id, 'user_id' => $this->vet->id, 'kind' => 'observation']);
    $payload = ['kind' => 'observation', 'title' => 'Korekta', 'description' => 'Uzupełnienie obserwacji.', 'occurred_on' => today()->toDateString(), 'correction_reason' => 'Pomyłka.'];
    $this->actingAs($this->carer)->post(route('animals.health.store', $this->animal), [...$payload, 'correction_of_id' => $other->id])->assertForbidden();
    $this->post(route('animals.health.store', $this->animal), [...$payload, 'correction_of_id' => $own->id])->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseCount('medical_records', 3);
});

test('foreign records corrections and attachments cannot cross shelter boundaries', function () {
    $foreign = MedicalRecord::factory()->create();
    $attachment = $foreign->attachments()->create(['path' => 'private.pdf', 'name' => 'private.pdf', 'size' => 10]);
    $this->actingAs($this->vet)->get(route('animals.health.index', $foreign->animal))->assertNotFound();
    $this->post(route('animals.health.store', $foreign->animal), $this->payload)->assertNotFound();
    $this->post(route('animals.health.store', $this->animal), [...$this->payload, 'correction_of_id' => $foreign->id, 'correction_reason' => 'Korekta'])->assertNotFound();
    $this->get(route('animals.health.download', [$this->animal, $attachment]))->assertNotFound();
    $this->get(route('animals.health.download', [$foreign->animal, $attachment]))->assertNotFound();
    $this->get(route('animals.health.index', $this->animal))->assertInertia(fn (Assert $page) => $page->has('records.data', 0));
});

test('follow up tasks require an active assignee from this shelter', function (string $kind) {
    $assignee = User::factory()->create(['organization_id' => $kind === 'foreign' ? Organization::factory()->create()->id : $this->organization->id, 'is_active' => $kind !== 'inactive', 'is_super_admin' => $kind === 'platform']);
    $this->actingAs($this->vet)->post(route('animals.health.store', $this->animal), [...$this->payload, 'next_due_date' => today()->addDay()->toDateString(), 'assignee_id' => $assignee->id])->assertSessionHasErrors('assignee_id');
    $this->assertDatabaseCount('medical_records', 0);
    $this->assertDatabaseCount('care_tasks', 0);
})->with(['foreign', 'inactive', 'platform']);

test('clinical fields validate dates weight and attachment limits', function () {
    $this->actingAs($this->vet)->post(route('animals.health.store', $this->animal), [...$this->payload, 'kind' => 'weight', 'occurred_on' => today()->addDay()->toDateString(), 'weight_kg' => -1, 'next_due_date' => today()->subDay()->toDateString(), 'attachments' => [UploadedFile::fake()->create('script.html', 1, 'text/html')]])->assertSessionHasErrors(['occurred_on', 'weight_kg', 'next_due_date', 'assignee_id', 'attachments.0']);
    $this->post(route('animals.health.store', $this->animal), [...$this->payload, 'attachments' => array_fill(0, 4, UploadedFile::fake()->image('photo.png'))])->assertSessionHasErrors('attachments');
    $this->post(route('animals.health.store', $this->animal), [...$this->payload, 'attachments' => [UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')]])->assertSessionHasErrors('attachments.0');
    $this->assertDatabaseCount('medical_records', 0);
});

test('filtering and pagination preserve clinical records and author names after account deletion', function () {
    MedicalRecord::factory()->count(13)->create(['animal_id' => $this->animal->id, 'kind' => 'vaccination']);
    MedicalRecord::factory()->create(['animal_id' => $this->animal->id, 'kind' => 'visit']);
    $this->actingAs($this->vet)->get(route('animals.health.index', [$this->animal, 'kind' => 'vaccination']))->assertInertia(fn (Assert $page) => $page->where('records.total', 13)->has('records.data', 12)->where('filter', 'vaccination'));
    $this->post(route('animals.health.store', $this->animal), $this->payload)->assertRedirect();
    $record = MedicalRecord::latest('id')->firstOrFail();
    $this->vet->delete();
    expect($record->fresh()->user_id)->toBeNull()->and($record->fresh()->author_name)->toBe($this->vet->name);
});

test('guests and platform administrators cannot access tenant medical documentation', function () {
    $this->get(route('animals.health.index', $this->animal))->assertRedirect(route('login'));
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin)->get(route('animals.health.index', $this->animal))->assertForbidden();
    $this->post(route('animals.health.store', $this->animal), $this->payload)->assertForbidden();
});

test('a correction cannot change the kind of an existing record', function () {
    $record = MedicalRecord::factory()->create(['animal_id' => $this->animal->id, 'kind' => 'vaccination']);
    $this->actingAs($this->vet)->withSession(['_previous.url' => route('animals.photo', $this->animal)])->post(route('animals.health.store', $this->animal), [...$this->payload, 'correction_of_id' => $record->id, 'correction_reason' => 'Pomyłka'])->assertSessionHasErrors('kind')->assertRedirect(route('animals.health.index', $this->animal));
    $this->assertDatabaseCount('medical_records', 1);
});

test('observation cannot include a clinical follow up or masquerade as recommendations', function () {
    $this->actingAs($this->carer)->post(route('animals.health.store', $this->animal), [...$this->payload, 'kind' => 'observation', 'next_due_date' => today()->addDay()->toDateString(), 'assignee_id' => $this->carer->id])->assertSessionHasErrors(['performed_by', 'recommendations', 'next_due_date']);
    $this->assertDatabaseCount('medical_records', 0);
    $this->assertDatabaseCount('care_tasks', 0);
});

test('visit form accepts empty optional fields sent by the browser', function () {
    $this->actingAs($this->vet)->post(route('animals.health.store', $this->animal), [...$this->payload, 'weight_kg' => '', 'correction_of_id' => null, 'correction_reason' => '', 'next_due_date' => '', 'assignee_id' => '', 'attachments' => []])->assertSessionHasNoErrors()->assertRedirect(route('animals.health.index', $this->animal));
});

test('invalid visit returns to the health form even when the previous URL is an animal photo', function () {
    $this->actingAs($this->vet)->withSession(['_previous.url' => route('animals.photo', $this->animal)])
        ->post(route('animals.health.store', $this->animal), [...$this->payload, 'title' => ''])
        ->assertSessionHasErrors('title')->assertRedirect(route('animals.health.index', $this->animal));
    $this->get(route('animals.health.index', $this->animal))
        ->assertInertia(fn (Assert $page) => $page->component('animals/Health'));
});
