<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalRecordRequest;
use App\Models\Animal;
use App\Models\CareTask;
use App\Models\MedicalAttachment;
use App\Models\MedicalRecord;
use App\Models\Organization;
use App\Models\User;
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

class MedicalRecordController extends Controller
{
    public function index(Request $request, Animal $animal): Response
    {
        Gate::authorize('access-animal', $animal);
        $filters = $request->validate(['kind' => ['nullable', Rule::in(array_keys(MedicalRecord::KINDS))]]);
        $query = MedicalRecord::query()->where('animal_id', $animal->id);
        if (! empty($filters['kind'])) {
            $query->where('kind', $filters['kind']);
        }

        return Inertia::render('animals/Health', [
            'animal' => $animal->only(['id', 'name', 'registration_number']),
            'records' => $query->with(['attachments', 'careTask:id,title,due_date,status'])->withCount('corrections')->orderByDesc('occurred_on')->orderByDesc('id')->paginate(12)->withQueryString(),
            'kinds' => MedicalRecord::KINDS,
            'filter' => $filters['kind'] ?? '',
            'canManageMedical' => $request->user()->can('manage-medical'),
            'members' => User::query()->where('organization_id', $animal->organization_id)->where('is_active', true)->where('is_super_admin', false)->orderBy('name')->get(['id', 'name']),
            'today' => today()->toDateString(),
        ]);
    }

    public function store(StoreMedicalRecordRequest $request, Animal $animal): RedirectResponse
    {
        $storedPaths = [];
        try {
            DB::transaction(function () use ($request, $animal, &$storedPaths): void {
                Organization::query()->lockForUpdate()->findOrFail($animal->organization_id);
                $actor = $request->user()->fresh();
                Gate::forUser($actor)->authorize('access-animal', $animal);
                if ($request->input('kind') !== 'observation') {
                    Gate::forUser($actor)->authorize('manage-medical');
                }
                $record = new MedicalRecord($request->safe()->only(['kind', 'title', 'occurred_on', 'performed_by', 'description', 'recommendations', 'weight_kg', 'correction_reason']));
                $record->animal()->associate($animal);
                $record->author()->associate($actor);
                $record->author_name = $actor->name;
                if ($request->filled('correction_of_id')) {
                    $original = MedicalRecord::query()->where('animal_id', $animal->id)->findOrFail($request->integer('correction_of_id'));
                    abort_unless($actor->can('manage-medical') || ($original->kind === 'observation' && $original->user_id === $actor->id), 403);
                    if ($original->kind !== $record->kind) {
                        throw ValidationException::withMessages(['kind' => 'Korekta musi zachować rodzaj pierwotnego wpisu.'])->redirectTo(route('animals.health.index', $animal));
                    }
                    $record->correction_of_id = $original->id;
                }
                if ($request->filled('next_due_date')) {
                    $assignee = User::query()->where('organization_id', $animal->organization_id)->where('is_active', true)->where('is_super_admin', false)->find($request->integer('assignee_id'));
                    if (! $assignee) {
                        throw ValidationException::withMessages(['assignee_id' => 'Wybierz aktywnego pracownika tego schroniska.'])->redirectTo(route('animals.health.index', $animal));
                    }
                    $task = new CareTask([
                        'title' => 'Kontrola: '.$record->title,
                        'description' => $record->recommendations ?: $record->description,
                        'assignee_id' => $assignee->id,
                        'due_date' => $request->validated('next_due_date'),
                        'priority' => 'normal',
                        'status' => 'todo',
                    ]);
                    $task->animal()->associate($animal);
                    $task->save();
                    $task->history()->create([
                        'user_id' => $actor->id, 'author_name' => $actor->name,
                        'note' => 'Zadanie utworzone z dokumentacji weterynaryjnej.',
                        'snapshot' => [...$task->only(['title', 'description', 'due_date', 'priority', 'status']), 'assignee_name' => $assignee->name],
                    ]);
                    $record->care_task_id = $task->id;
                }
                $record->save();
                foreach ($request->file('attachments', []) as $file) {
                    $path = $file->store('organizations/'.$animal->organization_id.'/medical', 'local');
                    abort_if($path === false, 500, 'Nie udało się zapisać załącznika. Spróbuj ponownie.');
                    $storedPaths[] = $path;
                    $record->attachments()->create(['path' => $path, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 255), 'size' => $file->getSize()]);
                }
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('local')->delete($storedPaths);
            }
            throw $exception;
        }
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Zapisano wpis w historii zdrowia zwierzęcia.']);

        return to_route('animals.health.index', $animal);
    }

    public function download(Animal $animal, MedicalAttachment $attachment): StreamedResponse
    {
        Gate::authorize('access-animal', $animal);
        abort_unless($attachment->medicalRecord->animal_id === $animal->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
