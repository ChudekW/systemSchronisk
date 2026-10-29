<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAnimalRequest;
use App\Models\Animal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Meilisearch\Exceptions\ApiException;
use Meilisearch\Exceptions\CommunicationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AnimalController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'species' => ['nullable', Rule::in(array_keys(Animal::SPECIES))],
            'status' => ['nullable', Rule::in(array_keys(Animal::STATUSES))],
        ]);
        $organizationId = $request->user()->organization_id;
        $scope = function (Builder $query) use ($organizationId, $filters): void {
            $query->where('organization_id', $organizationId);
            foreach (['species', 'status'] as $field) {
                if (! empty($filters[$field])) {
                    $query->where($field, $filters[$field]);
                }
            }
        };
        $searchUnavailable = false;
        if (isset($filters['search']) && $filters['search'] !== '') {
            $query = Animal::search($filters['search'])->where('organization_id', $organizationId)->query($scope);
            foreach (['species', 'status'] as $field) {
                if (! empty($filters[$field])) {
                    $query->where($field, $filters[$field]);
                }
            }
            try {
                $animals = $query->orderBy('admitted_at', 'desc')->orderBy('id', 'desc')->paginate(12);
            } catch (CommunicationException|ApiException $exception) {
                report($exception);
                $searchUnavailable = true;
                $animals = new LengthAwarePaginator([], 0, 12, options: ['path' => $request->url()]);
            }
        } else {
            $query = Animal::query();
            $scope($query);
            $animals = $query->orderByDesc('admitted_at')->orderByDesc('id')->paginate(12);
        }

        $animals->withQueryString();

        return Inertia::render('animals/Index', [
            'animals' => [
                'total' => $animals->total(),
                'current_page' => $animals->currentPage(),
                'last_page' => $animals->lastPage(),
                'prev_page_url' => $animals->previousPageUrl(),
                'next_page_url' => $animals->nextPageUrl(),
                'data' => array_map(fn (Animal $animal): array => [
                    ...$animal->only(['id', 'name', 'registration_number', 'species', 'sex', 'status', 'location', 'admitted_at']),
                    'has_photo' => $animal->photo_path !== null,
                ], $animals->items()),
            ],
            'filters' => $filters,
            'searchUnavailable' => $searchUnavailable,
            'options' => $this->options(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('animals/Form', ['animal' => null, 'options' => $this->options(), 'today' => today()->toDateString()]);
    }

    public function store(SaveAnimalRequest $request): RedirectResponse
    {
        $animal = new Animal;
        $animal->organization()->associate($request->user()->organization);
        $this->save($request, $animal);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dodano zwierzę do kartoteki schroniska.']);

        return to_route('animals.show', $animal);
    }

    public function show(Animal $animal): Response
    {
        Gate::authorize('access-animal', $animal);

        return Inertia::render('animals/Show', [
            'animal' => [...$animal->toArray(), 'has_photo' => $animal->photo_path !== null],
            'history' => $animal->statusHistory()->with('user:id,name')->latest('id')->paginate(15),
            'options' => $this->options(),
        ]);
    }

    public function edit(Animal $animal): Response
    {
        Gate::authorize('access-animal', $animal);

        return Inertia::render('animals/Form', [
            'animal' => [...$animal->toArray(), 'has_photo' => $animal->photo_path !== null],
            'options' => $this->options(),
            'today' => today()->toDateString(),
        ]);
    }

    public function update(SaveAnimalRequest $request, Animal $animal): RedirectResponse
    {
        $this->save($request, $animal);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zapisano kartę zwierzęcia.']);

        return to_route('animals.show', $animal);
    }

    public function photo(Animal $animal): StreamedResponse
    {
        Gate::authorize('access-animal', $animal);
        abort_unless($animal->photo_path && Storage::disk('local')->exists($animal->photo_path), 404);

        return Storage::disk('local')->response($animal->photo_path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function save(SaveAnimalRequest $request, Animal $animal): void
    {
        $newPhoto = $request->file('photo')?->store('organizations/'.$animal->organization_id.'/animals', 'local');
        abort_if($newPhoto === false, 500, 'Nie udało się zapisać zdjęcia. Spróbuj ponownie.');
        $oldPhoto = null;
        try {
            DB::transaction(function () use ($request, $animal, $newPhoto, &$oldPhoto): void {
                if ($animal->exists) {
                    $locked = Animal::query()->lockForUpdate()->findOrFail($animal->id);
                    $animal->setRawAttributes($locked->getAttributes(), true);
                }
                $oldStatus = $animal->status;
                $oldPhoto = $animal->photo_path;
                $animal->fill($request->safe()->except(['photo', 'remove_photo', 'status_note']));
                if ($newPhoto || $request->boolean('remove_photo')) {
                    $animal->photo_path = $newPhoto ?: null;
                }
                $animal->save();
                if ($oldStatus !== $animal->status) {
                    $animal->statusHistory()->create([
                        'user_id' => $request->user()->id,
                        'from_status' => $oldStatus,
                        'to_status' => $animal->status,
                        'note' => $request->validated('status_note'),
                    ]);
                }
            });
        } catch (Throwable $exception) {
            if ($newPhoto) {
                Storage::disk('local')->delete($newPhoto);
            }
            throw $exception;
        }
        if ($oldPhoto && $oldPhoto !== $animal->photo_path) {
            Storage::disk('local')->delete($oldPhoto);
        }
    }

    /** @return array<string, array<string, string>> */
    private function options(): array
    {
        return ['species' => Animal::SPECIES, 'sexes' => Animal::SEXES, 'statuses' => Animal::STATUSES, 'admissionReasons' => Animal::ADMISSION_REASONS, 'sterilization' => Animal::STERILIZATION];
    }
}
