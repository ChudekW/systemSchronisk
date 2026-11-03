<?php

use App\Models\Animal;
use App\Models\CareTask;
use App\Models\Organization;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->worker = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->animal = Animal::factory()->create(['organization_id' => $this->organization->id]);
    $this->payload = ['title' => 'Spacer', 'description' => 'Krótki spacer', 'assignee_id' => $this->worker->id, 'due_date' => today()->toDateString(), 'priority' => 'normal', 'status' => 'todo'];
});

test('shelter worker creates an assigned task and its immutable history', function () {
    $this->actingAs($this->worker)->post(route('animals.tasks.store', $this->animal), [...$this->payload, 'animal_id' => 999, 'user_id' => 999])->assertSessionHasNoErrors()->assertRedirect();
    $task = CareTask::sole();
    expect($task->animal_id)->toBe($this->animal->id)->and($task->assignee_id)->toBe($this->worker->id);
    $entry = $task->history()->sole();
    expect($entry->user_id)->toBe($this->worker->id)->and($entry->snapshot['title'])->toBe('Spacer')->and($entry->author_name)->toBe($this->worker->name);
    $this->get(route('animals.tasks.show', [$this->animal, $task]))->assertInertia(fn (Assert $page) => $page->component('tasks/Show')->has('history.data', 1));
});

test('task completion requires a note and reopening preserves completion history', function () {
    $this->actingAs($this->worker)->post(route('animals.tasks.store', $this->animal), $this->payload)->assertRedirect();
    $task = CareTask::sole();
    $url = route('animals.tasks.update', [$this->animal, $task]);
    $this->put($url, [...$this->payload, 'status' => 'done', 'version' => 1])->assertSessionHasErrors('note');
    expect($task->fresh()->status)->toBe('todo');
    $this->put($url, [...$this->payload, 'status' => 'done', 'version' => 1, 'note' => 'Spokojny spacer, dobry apetyt.'])->assertSessionHasNoErrors()->assertRedirect();
    $this->put($url, [...$this->payload, 'status' => 'in_progress', 'version' => 2, 'note' => 'Dodatkowa obserwacja.'])->assertSessionHasNoErrors()->assertRedirect();
    expect($task->fresh()->status)->toBe('in_progress')->and($task->history()->count())->toBe(3);
    $completed = $task->history()->orderBy('id')->get()[1];
    expect($completed->snapshot['status'])->toBe('done')->and($completed->note)->toBe('Spokojny spacer, dobry apetyt.');
});

test('a stale form cannot overwrite a newer task revision', function () {
    $task = CareTask::factory()->create(['animal_id' => $this->animal->id, 'assignee_id' => $this->worker->id]);
    $url = route('animals.tasks.update', [$this->animal, $task]);
    $this->actingAs($this->worker)->put($url, [...$this->payload, 'version' => 1, 'title' => 'Nowa wersja'])->assertRedirect();
    $this->put($url, [...$this->payload, 'version' => 1, 'title' => 'Stara wersja'])->assertSessionHasErrors('version');
    expect($task->fresh()->title)->toBe('Nowa wersja')->and($task->history()->count())->toBe(1);
});

test('tasks and their history cannot be accessed through another shelter or animal', function () {
    $foreignAnimal = Animal::factory()->create();
    $task = CareTask::factory()->create(['animal_id' => $foreignAnimal->id]);
    $this->actingAs($this->worker)->get(route('animals.tasks.index', $foreignAnimal))->assertNotFound();
    $this->post(route('animals.tasks.store', $foreignAnimal), $this->payload)->assertNotFound();
    foreach ([$foreignAnimal, $this->animal] as $animal) {
        $this->get(route('animals.tasks.show', [$animal, $task]))->assertNotFound();
        $this->put(route('animals.tasks.update', [$animal, $task]), [...$this->payload, 'version' => 1])->assertNotFound();
    }
    expect($task->history()->count())->toBe(0);
});

test('only active members of this shelter can be assigned tasks', function (string $kind) {
    $attributes = ['organization_id' => $this->organization->id];
    if ($kind === 'foreign') {
        $attributes['organization_id'] = Organization::factory()->create()->id;
    } elseif ($kind === 'inactive') {
        $attributes['is_active'] = false;
    } else {
        $attributes['is_super_admin'] = true;
    }
    $assignee = User::factory()->create($attributes);
    $this->actingAs($this->worker)->post(route('animals.tasks.store', $this->animal), [...$this->payload, 'assignee_id' => $assignee->id])->assertSessionHasErrors('assignee_id');
    $this->assertDatabaseCount('care_tasks', 0);
    $this->get(route('animals.tasks.index', $this->animal))->assertInertia(fn (Assert $page) => $page->has('options.members', 1)->where('options.members.0.id', $this->worker->id));
})->with(['foreign', 'inactive', 'platform']);

test('my tasks and overdue filtering exclude other workers completed tasks and other shelters', function () {
    $this->freezeTime();
    $base = ['animal_id' => $this->animal->id, 'assignee_id' => $this->worker->id];
    $late = CareTask::factory()->create([...$base, 'due_date' => today()->subDay()->toDateString()]);
    CareTask::factory()->create($base);
    CareTask::factory()->create([...$base, 'status' => 'done', 'due_date' => today()->subDays(2)->toDateString()]);
    CareTask::factory()->create(['animal_id' => $this->animal->id]);
    CareTask::factory()->create(['assignee_id' => $this->worker->id]);
    $this->actingAs($this->worker)->get(route('tasks.index'))->assertInertia(fn (Assert $page) => $page->has('tasks.data', 2)->where('overdueCount', 1));
    $this->get(route('tasks.index', ['filter' => 'overdue']))->assertInertia(fn (Assert $page) => $page->has('tasks.data', 1)->where('tasks.data.0.id', $late->id));
    $this->get(route('tasks.index', ['filter' => 'done']))->assertInertia(fn (Assert $page) => $page->has('tasks.data', 1)->where('tasks.data.0.status', 'done'));
    $this->get(route('animals.tasks.index', [$this->animal, 'filter' => 'all']))->assertInertia(fn (Assert $page) => $page->has('tasks.data', 4));
});

test('task history survives deletion of the worker account', function () {
    $this->actingAs($this->worker)->post(route('animals.tasks.store', $this->animal), $this->payload)->assertRedirect();
    $name = $this->worker->name;
    $this->worker->delete();
    $task = CareTask::sole();
    expect($task->assignee_id)->toBeNull()->and($task->history()->sole()->author_name)->toBe($name)->and($task->history()->sole()->user_id)->toBeNull();
});

test('guests and platform administrators cannot create shelter tasks', function () {
    $this->get(route('tasks.index'))->assertRedirect(route('login'));
    $this->post(route('animals.tasks.store', $this->animal), $this->payload)->assertRedirect(route('login'));
    $admin = User::factory()->create(['is_super_admin' => true]);
    $this->actingAs($admin)->get(route('tasks.index'))->assertForbidden();
    $this->post(route('animals.tasks.store', $this->animal), $this->payload)->assertForbidden();
});

test('task fields reject invalid dates statuses and priorities', function () {
    $this->actingAs($this->worker)->post(route('animals.tasks.store', $this->animal), [...$this->payload, 'title' => '', 'due_date' => '2026-02-31', 'status' => 'invalid', 'priority' => 'invalid'])->assertSessionHasErrors(['title', 'due_date', 'status', 'priority']);
    $this->assertDatabaseCount('care_tasks', 0);
});
