<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdoptionAftercareRequest;
use App\Models\AdoptionApplication;
use App\Models\Animal;
use App\Models\CareTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdoptionAftercareController extends Controller
{
    public function store(AdoptionAftercareRequest $request, AdoptionApplication $adoption): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $adoption): void {
                Organization::query()->lockForUpdate()->findOrFail($request->user()->organization_id);
                $animal = Animal::query()->lockForUpdate()->findOrFail($adoption->animal_id);
                $current = AdoptionApplication::query()->lockForUpdate()->findOrFail($adoption->id);
                $actor = $request->user()->fresh();
                Gate::forUser($actor)->authorize('access-animal', $animal);
                $returning = $request->routeIs('adoptions.return');
                if ($returning) {
                    Gate::forUser($actor)->authorize('manage-own-organization');
                }
                if ($current->version !== $request->integer('version')) {
                    throw ValidationException::withMessages(['version' => 'Zgłoszenie zostało zmienione. Odśwież stronę przed ponownym zapisem.']);
                }
                if ($current->status !== 'completed' || $animal->status !== 'adopted') {
                    throw ValidationException::withMessages(['version' => 'Ta adopcja nie jest już aktywna. Nie można dopisać kontaktu ani ponownie przyjąć zwierzęcia.']);
                }
                $dateField = $returning ? 'returned_on' : 'contact_on';
                if ($current->adopted_on === null || $request->validated($dateField) < $current->adopted_on) {
                    throw ValidationException::withMessages([$dateField => 'Data nie może poprzedzać wydania zwierzęcia do adopcji.']);
                }
                if ($returning) {
                    $current->status = 'returned';
                    $current->returned_on = $request->validated('returned_on');
                    $current->return_reason = $request->validated('return_reason');
                    $current->received_by = $request->validated('received_by');
                    $animal->status = 'admitted';
                    $animal->save();
                    $animal->statusHistory()->create(['user_id' => $actor->id, 'from_status' => 'adopted', 'to_status' => 'admitted', 'note' => 'Powrót z adopcji #'.$current->id.' dnia '.$current->returned_on.'. Przyjął(a): '.$current->received_by.'. Powód: '.$current->return_reason]);
                } elseif ($request->filled('task_title')) {
                    $assignee = User::where('organization_id', $animal->organization_id)->where('is_active', true)->where('is_super_admin', false)->find($request->integer('assignee_id'));
                    if (! $assignee) {
                        throw ValidationException::withMessages(['assignee_id' => 'Wybierz aktywnego pracownika tego schroniska.']);
                    }
                    $task = new CareTask(['title' => $request->validated('task_title'), 'description' => 'Kontakt poadopcyjny — zgłoszenie #'.$current->id, 'assignee_id' => $assignee->id, 'due_date' => $request->validated('task_due_date'), 'priority' => 'normal', 'status' => 'todo']);
                    $task->animal()->associate($animal);
                    $current->tasks()->save($task);
                    $task->history()->create(['user_id' => $actor->id, 'author_name' => $actor->name, 'note' => 'Zaplanowano kontakt poadopcyjny.', 'snapshot' => [...$task->only(['title', 'description', 'due_date', 'priority', 'status']), 'assignee_name' => $assignee->name]]);
                }
                $current->version++;
                $current->save();
                $current->history()->create([
                    'author_name' => $actor->name,
                    'note' => $returning ? $current->return_reason : $request->validated('note'),
                    'snapshot' => [...$current->only(['candidate_name', 'email', 'phone', 'housing', 'candidate_notes', 'status', 'adopted_on', 'returned_on', 'return_reason', 'received_by']), 'coordinator_name' => $current->coordinator()->value('name'), 'event_type' => $returning ? 'return' : 'aftercare', 'contact_on' => $returning ? null : $request->validated('contact_on')],
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('adoptions.show', $adoption));
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => $request->routeIs('adoptions.return') ? 'Zarejestrowano powrót. Zwierzę ponownie jest pod opieką schroniska.' : 'Zapisano kontakt poadopcyjny.']);

        return to_route('adoptions.show', $adoption);
    }
}
