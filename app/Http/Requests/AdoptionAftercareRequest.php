<?php

namespace App\Http\Requests;

use App\Models\AdoptionApplication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class AdoptionAftercareRequest extends FormRequest
{
    public function authorize(): bool
    {
        $adoption = $this->route('adoption');
        abort_unless($adoption instanceof AdoptionApplication, 404);
        Gate::authorize('access-animal', $adoption->animal);
        if ($this->routeIs('adoptions.return')) {
            Gate::authorize('manage-own-organization');
        }

        return true;
    }

    protected function getRedirectUrl(): string
    {
        return route('adoptions.show', $this->route('adoption'));
    }

    /** @return array<string, array<int, string|Exists>> */
    public function rules(): array
    {
        $rules = ['version' => ['required', 'integer', 'min:1']];
        if ($this->routeIs('adoptions.return')) {
            return [...$rules,
                'returned_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
                'return_reason' => ['required', 'string', 'max:5000'],
                'received_by' => ['required', 'string', 'max:255'],
            ];
        }

        return [...$rules,
            'contact_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['required', 'string', 'max:5000'],
            'task_title' => ['nullable', 'string', 'max:160'],
            'task_due_date' => ['exclude_without:task_title', 'required_with:task_title', 'date_format:Y-m-d', 'after_or_equal:today'],
            'assignee_id' => ['exclude_without:task_title', 'required_with:task_title', 'integer', Rule::exists('users', 'id')->where('organization_id', $this->user()->organization_id)->where('is_active', true)->where('is_super_admin', false)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['returned_on' => 'data powrotu', 'return_reason' => 'powód powrotu', 'received_by' => 'osoba przyjmująca', 'contact_on' => 'data kontaktu / ustalenia', 'note' => 'notatka', 'task_title' => 'zadanie', 'task_due_date' => 'termin zadania', 'assignee_id' => 'osoba odpowiedzialna'];
    }
}
