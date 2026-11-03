<?php

namespace App\Http\Requests;

use App\Models\Animal;
use App\Models\CareTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;

class SaveCareTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $animal = $this->route('animal');
        abort_unless($animal instanceof Animal, 404);
        Gate::authorize('access-animal', $animal);
        $task = $this->route('task');
        abort_if($task instanceof CareTask && $task->animal_id !== $animal->id, 404);

        return true;
    }

    /** @return array<string, array<int, string|Exists|In>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assignee_id' => ['required', 'integer', Rule::exists('users', 'id')->where('organization_id', $this->user()->organization_id)->where('is_active', true)->where('is_super_admin', false)],
            'due_date' => ['required', 'date_format:Y-m-d'],
            'priority' => ['required', Rule::in(array_keys(CareTask::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(CareTask::STATUSES))],
            'note' => ['nullable', 'required_if:status,done', 'string', 'max:5000'],
            'version' => [$this->isMethod('put') ? 'required' : 'nullable', 'integer', 'min:1'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['title' => 'tytuł', 'description' => 'opis', 'assignee_id' => 'osoba odpowiedzialna', 'due_date' => 'termin', 'priority' => 'priorytet', 'status' => 'status', 'note' => 'notatka z wykonania'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['assignee_id.exists' => 'Wybierz aktywnego pracownika tego schroniska.', 'note.required_if' => 'Opisz wykonane czynności przed zakończeniem zadania.'];
    }
}
