<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCareTaskRequest;
use App\Models\Animal;
use App\Models\CareTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CareTaskController extends Controller
{
    public function index(Request $request, ?Animal $animal = null): Response
    {
        if ($animal) {
            Gate::authorize('access-animal', $animal);
        }
        $filters = $request->validate(['filter' => ['nullable', Rule::in(['open', 'overdue', 'done', 'all'])]]);
        $filter = $filters['filter'] ?? 'open';
        $query = CareTask::query()->whereRelation('animal', 'organization_id', $request->user()->organization_id);
        if ($animal) {
            $query->where('animal_id', $animal->id);
        } else {
            $query->where('assignee_id', $request->user()->id);
        }
        $overdueCount = (clone $query)->where('status', '!=', 'done')->where('due_date', '<', today()->toDateString())->count();
        if ($filter === 'open' || $filter === 'overdue') {
            $query->where('status', '!=', 'done');
        }
        if ($filter === 'overdue') {
            $query->where('due_date', '<', today()->toDateString());
        }
        if ($filter === 'done') {
            $query->where('status', 'done');
        }

        return Inertia::render('tasks/Index', [
            'animal' => $animal?->only(['id', 'name', 'registration_number']),
            'tasks' => $query->with(['animal:id,name,registration_number', 'assignee:id,name,is_active'])->orderBy('due_date')->orderBy('id')->paginate(15)->withQueryString(),
            'filter' => $filter,
            'overdueCount' => $overdueCount,
            'today' => today()->toDateString(),
            'options' => $this->options($request),
        ]);
    }

    public function show(Request $request, Animal $animal, CareTask $task): Response
    {
        Gate::authorize('access-animal', $animal);
        abort_unless($task->animal_id === $animal->id, 404);

        return Inertia::render('tasks/Show', [
            'animal' => $animal->only(['id', 'name', 'registration_number']),
            'task' => $task->load('assignee:id,name,is_active'),
            'history' => $task->history()->latest('id')->paginate(15),
            'options' => $this->options($request),
        ]);
    }

    public function store(SaveCareTaskRequest $request, Animal $animal): RedirectResponse
    {
        $task = new CareTask;
        $task->animal()->associate($animal);
        $this->save($request, $task);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Dodano zadanie do planu opieki.']);

        return to_route('animals.tasks.show', [$animal, $task]);
    }

    public function update(SaveCareTaskRequest $request, Animal $animal, CareTask $task): RedirectResponse
    {
        $this->save($request, $task);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zapisano zadanie i historię opieki.']);

        return to_route('animals.tasks.show', [$animal, $task]);
    }

    private function save(SaveCareTaskRequest $request, CareTask $task): void
    {
        DB::transaction(function () use ($request, $task): void {
            Organization::query()->lockForUpdate()->findOrFail($request->user()->organization_id);
            Gate::forUser($request->user()->fresh())->authorize('access-animal', $task->animal);
            $assignee = User::query()->where('organization_id', $request->user()->organization_id)->where('is_active', true)->where('is_super_admin', false)->find($request->integer('assignee_id'));
            if (! $assignee) {
                throw ValidationException::withMessages(['assignee_id' => 'Wybierz aktywnego pracownika tego schroniska.']);
            }
            if ($task->exists) {
                $locked = CareTask::query()->lockForUpdate()->findOrFail($task->id);
                if ($locked->version !== $request->integer('version')) {
                    throw ValidationException::withMessages(['version' => 'Ktoś zmienił to zadanie. Odśwież stronę i sprawdź aktualne dane przed ponownym zapisem.']);
                }
                $task->setRawAttributes($locked->getAttributes(), true);
                $task->version++;
            }
            $task->fill($request->safe()->only(['title', 'description', 'assignee_id', 'due_date', 'priority', 'status']));
            $task->save();
            $task->history()->create([
                'user_id' => $request->user()->id,
                'author_name' => $request->user()->name,
                'note' => $request->validated('note'),
                'snapshot' => [...$task->only(['title', 'description', 'due_date', 'priority', 'status']), 'assignee_name' => $assignee->name],
            ]);
        });
    }

    /** @return array<string, mixed> */
    private function options(Request $request): array
    {
        return [
            'statuses' => CareTask::STATUSES,
            'priorities' => CareTask::PRIORITIES,
            'members' => User::query()->where('organization_id', $request->user()->organization_id)->where('is_active', true)->where('is_super_admin', false)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
