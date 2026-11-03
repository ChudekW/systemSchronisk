<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAdoptionRequest;
use App\Models\AdoptionApplication;
use App\Models\AdoptionAttachment;
use App\Models\Animal;
use App\Models\CareTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AdoptionController extends Controller
{
    public function index(Request $request, ?Animal $animal = null): Response
    {
        if ($animal) {
            Gate::authorize('access-animal', $animal);
        }
        $filters = $request->validate(['status' => ['nullable', Rule::in(['open', ...array_keys(AdoptionApplication::STATUSES)])]]);
        $filter = $filters['status'] ?? 'open';
        $query = AdoptionApplication::query()->whereRelation('animal', 'organization_id', $request->user()->organization_id);
        if ($animal) {
            $query->where('animal_id', $animal->id);
        }
        if ($filter === 'open') {
            $query->whereIn('status', AdoptionApplication::OPEN);
        } else {
            $query->where('status', $filter);
        }

        return Inertia::render('adoptions/Index', [
            'animal' => $animal?->only(['id', 'name', 'registration_number']),
            'applications' => $query->select(['id', 'animal_id', 'candidate_name', 'coordinator_id', 'status', 'created_at'])->with(['animal:id,name,registration_number', 'coordinator:id,name'])->latest('id')->paginate(15)->withQueryString(),
            'filter' => $filter, 'statuses' => AdoptionApplication::STATUSES,
            'members' => $this->members($request),
            'today' => today()->toDateString(),
        ]);
    }

    public function show(Request $request, AdoptionApplication $adoption): Response
    {
        Gate::authorize('access-animal', $adoption->animal);
        $canDecide = $request->user()->can('manage-own-organization');
        $transitions = AdoptionApplication::TRANSITIONS[$adoption->status];
        if (! $canDecide) {
            $transitions = array_values(array_diff($transitions, ['approved', 'completed', 'rejected']));
        }

        return Inertia::render('adoptions/Show', [
            'application' => $adoption->load(['animal:id,name,registration_number', 'attachments', 'coordinator:id,name']),
            'history' => $adoption->history()->latest('id')->paginate(15, ['*'], 'history_page')->withQueryString(),
            'tasks' => $adoption->tasks()->latest('id')->paginate(10, ['id', 'title', 'status', 'animal_id', 'due_date'], 'tasks_page')->withQueryString(),
            'statuses' => AdoptionApplication::STATUSES, 'transitions' => $transitions,
            'canRegisterReturn' => $canDecide && $adoption->status === 'completed',
            'editable' => in_array($adoption->status, AdoptionApplication::OPEN, true) && ($adoption->status !== 'approved' || $canDecide),
            'members' => $this->members($request), 'today' => today()->toDateString(),
        ]);
    }

    public function store(SaveAdoptionRequest $request, Animal $animal): RedirectResponse
    {
        $application = new AdoptionApplication;
        $application->animal()->associate($animal);
        $this->save($request, $application);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dodano zgłoszenie adopcyjne.']);

        return to_route('adoptions.show', $application);
    }

    public function update(SaveAdoptionRequest $request, AdoptionApplication $adoption): RedirectResponse
    {
        $this->save($request, $adoption);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zapisano proces adopcji i historię zmian.']);

        return to_route('adoptions.show', $adoption);
    }

    private function save(SaveAdoptionRequest $request, AdoptionApplication $application): void
    {
        $paths = [];
        $redirect = $application->exists ? route('adoptions.show', $application) : route('animals.adoptions.index', $application->animal_id);
        try {
            DB::transaction(function () use ($request, $application, &$paths): void {
                Organization::query()->lockForUpdate()->findOrFail($request->user()->organization_id);
                $animal = Animal::query()->lockForUpdate()->findOrFail($application->animal_id);
                $actor = $request->user()->fresh();
                Gate::forUser($actor)->authorize('access-animal', $animal);
                if ($application->exists) {
                    $locked = AdoptionApplication::query()->lockForUpdate()->findOrFail($application->id);
                    if ($locked->version !== $request->integer('version')) {
                        throw ValidationException::withMessages(['version' => 'Zgłoszenie zostało zmienione. Odśwież stronę przed ponownym zapisem.']);
                    }
                    $application->setRawAttributes($locked->getAttributes(), true);
                    if (! in_array($application->status, AdoptionApplication::OPEN, true)) {
                        throw ValidationException::withMessages(['status' => 'Zakończone zgłoszenie pozostaje w historii i nie może być zmieniane.']);
                    }
                    if ($application->status === 'approved') {
                        Gate::forUser($actor)->authorize('manage-own-organization');
                    }
                    $target = $request->validated('status');
                    if ($target !== $application->status && ! in_array($target, AdoptionApplication::TRANSITIONS[$application->status], true)) {
                        throw ValidationException::withMessages(['status' => 'Niedozwolona zmiana etapu. Najpierw przeprowadź poprzednie kroki.']);
                    }
                    $application->version++;
                } elseif ($request->validated('status') !== 'new') {
                    throw ValidationException::withMessages(['status' => 'Nowe zgłoszenie musi rozpocząć się od etapu „Nowe”.']);
                }
                $target = $request->validated('status');
                if (in_array($target, ['approved', 'completed', 'rejected'], true)) {
                    Gate::forUser($actor)->authorize('manage-own-organization');
                }
                if (! $application->exists || in_array($target, ['approved', 'completed'], true)) {
                    if (in_array($animal->status, ['adopted', 'returned', 'transferred', 'deceased'], true) || AdoptionApplication::where('animal_id', $animal->id)->where('status', 'completed')->exists()) {
                        throw ValidationException::withMessages(['status' => 'Zwierzę nie jest dostępne do adopcji.']);
                    }
                }
                if (in_array($target, ['approved', 'completed'], true) && ! in_array($animal->status, ['ready', 'reserved'], true)) {
                    throw ValidationException::withMessages(['status' => 'Przed decyzją oznacz zwierzę jako gotowe do adopcji lub zarezerwowane.']);
                }
                $coordinator = User::where('organization_id', $animal->organization_id)->where('is_active', true)->where('is_super_admin', false)->find($request->integer('coordinator_id'));
                if (! $coordinator) {
                    throw ValidationException::withMessages(['coordinator_id' => 'Wybierz aktywnego pracownika tego schroniska.']);
                }
                if ($target === 'completed') {
                    if (! $application->attachments()->exists() && ! $request->hasFile('attachments')) {
                        throw ValidationException::withMessages(['attachments' => 'Przed wydaniem dołącz dokument adopcji.']);
                    }
                    if ($request->validated('adopted_on') < substr((string) $animal->getRawOriginal('admitted_at'), 0, 10)) {
                        throw ValidationException::withMessages(['adopted_on' => 'Data wydania nie może poprzedzać przyjęcia zwierzęcia.']);
                    }
                }
                $application->fill($request->safe()->only(['candidate_name', 'email', 'phone', 'housing', 'candidate_notes', 'coordinator_id', 'status', 'adopted_on']));
                $application->save();
                foreach ($request->file('attachments', []) as $file) {
                    $path = $file->store('organizations/'.$animal->organization_id.'/adoptions', 'local');
                    abort_if($path === false, 500, 'Nie udało się zapisać dokumentu.');
                    $paths[] = $path;
                    $application->attachments()->create(['path' => $path, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 255)]);
                }
                $this->history($application, $actor->name, $request->validated('note'));
                if ($request->filled('task_title')) {
                    if (! in_array($target, [...AdoptionApplication::OPEN, 'completed'], true)) {
                        throw ValidationException::withMessages(['task_title' => 'Nowe zadania dodawaj do otwartych lub zakończonych adopcją zgłoszeń.']);
                    }
                    $task = new CareTask(['title' => $request->validated('task_title'), 'description' => 'Zadanie dotyczące zgłoszenia adopcyjnego #'.$application->id, 'assignee_id' => $coordinator->id, 'due_date' => $request->validated('task_due_date'), 'status' => 'todo', 'priority' => 'normal']);
                    $task->animal()->associate($animal);
                    $application->tasks()->save($task);
                    $task->history()->create(['author_name' => $actor->name, 'user_id' => $actor->id, 'note' => 'Utworzono zadanie procesu adopcyjnego.', 'snapshot' => [...$task->only(['title', 'description', 'due_date', 'priority', 'status']), 'assignee_name' => $coordinator->name]]);
                }
                if ($target === 'completed') {
                    $oldStatus = $animal->status;
                    $animal->status = 'adopted';
                    $animal->save();
                    $animal->statusHistory()->create(['user_id' => $actor->id, 'from_status' => $oldStatus, 'to_status' => 'adopted', 'note' => 'Wydanie zwierzęcia — adopcja #'.$application->id.'.']);
                    foreach (AdoptionApplication::where('animal_id', $animal->id)->where('id', '!=', $application->id)->whereIn('status', AdoptionApplication::OPEN)->lockForUpdate()->get() as $other) {
                        $other->status = 'withdrawn';
                        $other->version++;
                        $other->save();
                        $this->history($other, $actor->name, 'Zamknięto automatycznie: zwierzę zostało adoptowane w innym zgłoszeniu.');
                    }
                }
            });
        } catch (Throwable $exception) {
            if ($paths !== []) {
                Storage::disk('local')->delete($paths);
            }
            if ($exception instanceof ValidationException) {
                $exception->redirectTo($redirect);
            }
            throw $exception;
        }
    }

    private function history(AdoptionApplication $application, string $author, ?string $note): void
    {
        $application->history()->create(['author_name' => $author, 'note' => $note, 'snapshot' => [...$application->only(['candidate_name', 'email', 'phone', 'housing', 'candidate_notes', 'status', 'adopted_on']), 'coordinator_name' => $application->coordinator()->value('name')]]);
    }

    /** @return Collection<int, User> */
    private function members(Request $request): Collection
    {
        return User::where('organization_id', $request->user()->organization_id)->where('is_active', true)->where('is_super_admin', false)->orderBy('name')->get(['id', 'name']);
    }

    public function download(AdoptionAttachment $attachment): StreamedResponse
    {
        Gate::authorize('access-animal', $attachment->adoptionApplication->animal);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
