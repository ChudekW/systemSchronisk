<?php

use App\Models\AdoptionApplication;
use App\Models\Animal;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    $this->organization = Organization::factory()->create();
    $this->admin = User::factory()->create(['organization_id' => $this->organization->id, 'is_organization_admin' => true]);
    $this->worker = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->animal = Animal::factory()->create(['organization_id' => $this->organization->id, 'status' => 'adopted']);
    $this->adoption = AdoptionApplication::factory()->create(['animal_id' => $this->animal->id, 'coordinator_id' => $this->worker->id, 'status' => 'completed', 'adopted_on' => today()->subDays(10)->toDateString()]);
    $this->returnPayload = ['version' => 1, 'returned_on' => today()->toDateString(), 'return_reason' => 'Zmiana sytuacji opiekuna.', 'received_by' => 'Anna Kowalska'];
});

test('worker appends aftercare notes and tasks without changing the completed adoption', function () {
    $name = $this->adoption->candidate_name;
    $this->actingAs($this->worker)->post(route('adoptions.aftercare', $this->adoption), ['version' => 1, 'contact_on' => today()->toDateString(), 'note' => 'Dobra adaptacja, kolejny kontakt za tydzień.', 'task_title' => 'Kontakt poadopcyjny', 'task_due_date' => today()->addWeek()->toDateString(), 'assignee_id' => $this->worker->id, 'candidate_name' => 'Nadpisanie', 'status' => 'returned'])->assertSessionHasNoErrors()->assertRedirect(route('adoptions.show', $this->adoption));
    expect($this->adoption->fresh()->status)->toBe('completed')->and($this->adoption->fresh()->candidate_name)->toBe($name)->and($this->adoption->tasks()->sole()->assignee_id)->toBe($this->worker->id)->and($this->adoption->history()->sole()->snapshot['event_type'])->toBe('aftercare')->and($this->animal->fresh()->status)->toBe('adopted');
    $this->get(route('adoptions.show', $this->adoption))->assertInertia(fn (Assert $page) => $page->where('canRegisterReturn', false)->where('editable', false));
});

test('administrator records a return preserving adoption data and enabling another adoption', function () {
    $document = $this->adoption->attachments()->create(['path' => 'original.pdf', 'name' => 'original.pdf']);
    $oldDate = $this->adoption->adopted_on;
    $this->actingAs($this->admin)->post(route('adoptions.return', $this->adoption), $this->returnPayload)->assertSessionHasNoErrors()->assertRedirect();
    expect($this->adoption->fresh()->status)->toBe('returned')->and($this->adoption->fresh()->adopted_on)->toBe($oldDate)->and($this->adoption->fresh()->received_by)->toBe('Anna Kowalska')->and($this->animal->fresh()->status)->toBe('admitted');
    $this->assertModelExists($document);
    $this->assertDatabaseHas('animal_status_histories', ['animal_id' => $this->animal->id, 'from_status' => 'adopted', 'to_status' => 'admitted']);
    $this->get(route('adoptions.index', ['status' => 'returned']))->assertInertia(fn (Assert $page) => $page->has('applications.data', 1));
    $payload = ['candidate_name' => 'Nowy opiekun', 'email' => 'new@example.com', 'housing' => 'Dom.', 'coordinator_id' => $this->worker->id, 'status' => 'new'];
    $this->post(route('animals.adoptions.store', $this->animal), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $new = AdoptionApplication::where('status', 'new')->sole();
    foreach (['reviewing', 'meeting'] as $index => $status) {
        $this->put(route('adoptions.update', $new), [...$payload, 'status' => $status, 'version' => $index + 1])->assertSessionHasNoErrors()->assertRedirect();
    }
    $this->put(route('adoptions.update', $new), [...$payload, 'status' => 'approved', 'version' => 3, 'note' => 'Decyzja'])->assertSessionHasErrors('status');
    $this->animal->update(['status' => 'ready']);
    $this->put(route('adoptions.update', $new), [...$payload, 'status' => 'approved', 'version' => 3, 'note' => 'Decyzja po przygotowaniu'])->assertSessionHasNoErrors()->assertRedirect();
    $this->put(route('adoptions.update', $new), [...$payload, 'status' => 'completed', 'version' => 4, 'note' => 'Wydanie', 'adopted_on' => today()->toDateString(), 'attachments' => [UploadedFile::fake()->image('umowa.png')], 'task_title' => 'Pierwszy kontakt po adopcji', 'task_due_date' => today()->addWeek()->toDateString()])->assertSessionHasNoErrors()->assertRedirect();
    expect($new->fresh()->status)->toBe('completed')->and($new->tasks()->count())->toBe(1)->and($this->adoption->fresh()->status)->toBe('returned')->and(AdoptionApplication::where('status', 'completed')->count())->toBe(1);
    $this->post(route('adoptions.return', $this->adoption), [...$this->returnPayload, 'version' => 2])->assertSessionHasErrors('version');
    expect($this->animal->fresh()->status)->toBe('adopted');
});

test('worker cannot register a return and other shelters cannot access aftercare', function () {
    $this->actingAs($this->worker)->post(route('adoptions.return', $this->adoption), $this->returnPayload)->assertForbidden();
    $foreign = User::factory()->create(['organization_id' => Organization::factory()->create()->id, 'is_organization_admin' => true]);
    $this->actingAs($foreign)->post(route('adoptions.return', $this->adoption), $this->returnPayload)->assertNotFound();
    $this->post(route('adoptions.aftercare', $this->adoption), ['version' => 1, 'contact_on' => today()->toDateString(), 'note' => 'Obca notatka'])->assertNotFound();
    expect($this->adoption->history()->count())->toBe(0)->and($this->adoption->fresh()->status)->toBe('completed');
});

test('return is single use and stale forms cannot create duplicate notes or tasks', function () {
    $note = ['version' => 1, 'contact_on' => today()->toDateString(), 'note' => 'Rozmowa.'];
    $this->actingAs($this->admin)->post(route('adoptions.aftercare', $this->adoption), $note)->assertSessionHasNoErrors()->assertRedirect();
    $this->post(route('adoptions.aftercare', $this->adoption), $note)->assertSessionHasErrors('version');
    $this->post(route('adoptions.return', $this->adoption), $this->returnPayload)->assertSessionHasErrors('version');
    $this->post(route('adoptions.return', $this->adoption), [...$this->returnPayload, 'version' => 2])->assertSessionHasNoErrors()->assertRedirect();
    $this->post(route('adoptions.return', $this->adoption), [...$this->returnPayload, 'version' => 3])->assertSessionHasErrors('version');
    expect($this->adoption->history()->count())->toBe(2);
});

test('dates cannot precede adoption or be in the future and required return data is enforced', function () {
    $this->actingAs($this->admin)->post(route('adoptions.return', $this->adoption), [...$this->returnPayload, 'returned_on' => today()->addDay()->toDateString()])->assertSessionHasErrors('returned_on');
    $this->post(route('adoptions.return', $this->adoption), [...$this->returnPayload, 'returned_on' => today()->subYear()->toDateString()])->assertSessionHasErrors('returned_on');
    $this->post(route('adoptions.return', $this->adoption), ['version' => 1])->assertSessionHasErrors(['returned_on', 'return_reason', 'received_by']);
    $this->post(route('adoptions.aftercare', $this->adoption), ['version' => 1, 'contact_on' => today()->subYear()->toDateString(), 'note' => 'Rozmowa'])->assertSessionHasErrors('contact_on');
    expect($this->adoption->history()->count())->toBe(0);
});

test('aftercare requires an active local task assignee and returns errors to the application', function () {
    $foreign = User::factory()->create();
    $this->actingAs($this->worker)->withSession(['_previous.url' => route('animals.photo', $this->animal)])->post(route('adoptions.aftercare', $this->adoption), ['version' => 1, 'contact_on' => today()->toDateString(), 'note' => 'Kontakt', 'task_title' => 'Zadanie', 'task_due_date' => today()->toDateString(), 'assignee_id' => $foreign->id])->assertSessionHasErrors('assignee_id')->assertRedirect(route('adoptions.show', $this->adoption));
    expect($this->adoption->tasks()->count())->toBe(0);
});
