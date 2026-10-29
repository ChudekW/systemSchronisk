<?php

use App\Models\Animal;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\MeilisearchEngine;
use Laravel\Scout\Jobs\MakeSearchable;
use Meilisearch\Client;
use Meilisearch\Contracts\TasksQuery;
use Meilisearch\Exceptions\CommunicationException;

uses(RefreshDatabase::class);

test('search indexes only the fields required for lookup and filtering', function () {
    $animal = Animal::factory()->create(['health_notes' => 'Internal health note', 'photo_path' => 'private/photo.jpg']);

    expect(array_keys($animal->toSearchableArray()))->toBe([
        'id', 'organization_id', 'name', 'registration_number', 'microchip', 'breed', 'color', 'location', 'species', 'status', 'admitted_at',
    ]);
    expect($animal->toSearchableArray()['organization_id'])->toBeInt();
});

test('an unavailable search service shows a recoverable state while browsing still works', function () {
    $animal = Animal::factory()->create();
    $user = User::factory()->create(['organization_id' => $animal->organization_id]);
    Exceptions::fake();
    $engine = Mockery::mock(MeilisearchEngine::class);
    $engine->shouldReceive('paginate')->once()->andThrow(new CommunicationException('Search unavailable'));
    app(EngineManager::class)->extend('unavailable', fn () => $engine);
    config(['scout.driver' => 'unavailable']);

    $this->actingAs($user)->get(route('animals.index', ['search' => 'Luna']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('searchUnavailable', true)->has('animals.data', 0));
    Exceptions::assertReported(CommunicationException::class);
    $this->get(route('animals.index'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('searchUnavailable', false)->has('animals.data', 1));
});

test('database hydration rejects a foreign animal even if the search index is stale', function () {
    $animal = Animal::factory()->create();
    $user = User::factory()->create(['organization_id' => Organization::factory()->create()->id]);
    $engine = Mockery::mock(MeilisearchEngine::class)->makePartial();
    $engine->shouldReceive('paginate')->once()->andReturn(['hits' => [['id' => $animal->id]], 'totalHits' => 1]);
    app(EngineManager::class)->extend('stale', fn () => $engine);
    config(['scout.driver' => 'stale']);

    $this->actingAs($user)->get(route('animals.index', ['search' => $animal->name]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('animals.data', 0));
});

test('animal indexing is queued only after a successful transaction', function () {
    Bus::fake([MakeSearchable::class]);
    config(['scout.queue' => ['queue' => 'scout']]);
    $organization = Organization::factory()->create();

    DB::beginTransaction();
    Animal::factory()->create(['organization_id' => $organization->id]);
    Bus::assertNotDispatched(MakeSearchable::class);
    DB::rollBack();
    Bus::assertNotDispatched(MakeSearchable::class);

    DB::transaction(fn () => Animal::factory()->create(['organization_id' => $organization->id]));
    Bus::assertDispatched(MakeSearchable::class, fn (MakeSearchable $job) => $job->queue === 'scout');
});

test('meilisearch supports typos tenant filters pagination updates and deletion', function () {
    config(['scout.driver' => 'meilisearch', 'scout.prefix' => 'test_'.Str::uuid().'_', 'scout.queue' => false]);
    app(EngineManager::class)->forgetDrivers();
    $client = app(Client::class);
    $indexName = (new Animal)->indexableAs();
    $index = $client->index($indexName);
    $waitForIndex = function () use ($client, $index): void {
        $task = $index->getTasks((new TasksQuery)->setLimit(1))->getResults()[0];
        expect($client->waitForTask($task['uid'], 10000)['status'])->toBe('succeeded');
    };
    try {
        $settings = $index->updateSettings(config('scout.meilisearch.index-settings.'.Animal::class));
        expect($client->waitForTask($settings['taskUid'], 10000)['status'])->toBe('succeeded');
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $organization->id]);
        $animals = Animal::factory()->count(13)->create(['organization_id' => $organization->id, 'name' => 'Bursztyn', 'species' => 'dog', 'status' => 'ready']);
        $foreign = Animal::factory()->create(['name' => 'Bursztyn', 'status' => 'ready']);
        $cat = Animal::factory()->create(['organization_id' => $organization->id, 'name' => 'Bursztyn', 'species' => 'cat', 'status' => 'ready']);
        $treated = Animal::factory()->create(['organization_id' => $organization->id, 'name' => 'Bursztyn', 'status' => 'treatment']);
        $animals->concat([$foreign, $cat, $treated])->searchable();
        $waitForIndex();

        $filters = ['search' => 'Bursztyn', 'species' => 'dog', 'status' => 'ready'];
        $this->actingAs($user)->get(route('animals.index', [...$filters, 'search' => 'Bursztym']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('searchUnavailable', false)->where('animals.total', 13)->has('animals.data', 12));
        $this->get(route('animals.index', [...$filters, 'page' => 2]))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('animals.total', 13)->has('animals.data', 1));

        $animal = $animals->first();
        $animal->update(['name' => 'Ametyst', 'microchip' => '123456789012345']);
        $animal->searchable();
        $waitForIndex();
        $this->get(route('animals.index', ['search' => '123456789012345']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('animals.data', 1)->where('animals.data.0.id', $animal->id));
        $this->get(route('animals.index', ['search' => '123456789012346']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('animals.data', 0));
        $animal->unsearchable();
        $waitForIndex();
        $this->get(route('animals.index', ['search' => 'Ametyst']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('animals.data', 0));
    } finally {
        $task = $client->deleteIndex($indexName);
        $client->waitForTask($task['taskUid'], 10000);
    }
})->skip(fn () => ! getenv('RUN_MEILISEARCH_TESTS'), 'Run explicitly against Sail Meilisearch with an isolated test index.');
