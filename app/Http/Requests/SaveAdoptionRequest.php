<?php

namespace App\Http\Requests;

use App\Models\AdoptionApplication;
use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;

class SaveAdoptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $adoption = $this->route('adoption');
        $animal = $adoption instanceof AdoptionApplication ? $adoption->animal : $this->route('animal');
        abort_unless($animal instanceof Animal, 404);
        Gate::authorize('access-animal', $animal);

        return true;
    }

    protected function getRedirectUrl(): string
    {
        $adoption = $this->route('adoption');

        return $adoption instanceof AdoptionApplication ? route('adoptions.show', $adoption) : route('animals.adoptions.index', $this->route('animal'));
    }

    /** @return array<string, array<int, string|Exists|In>> */
    public function rules(): array
    {
        return [
            'candidate_name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:50'],
            'housing' => ['required', 'string', 'max:5000'],
            'candidate_notes' => ['nullable', 'string', 'max:5000'],
            'coordinator_id' => ['required', 'integer', Rule::exists('users', 'id')->where('organization_id', $this->user()->organization_id)->where('is_active', true)->where('is_super_admin', false)],
            'status' => ['required', Rule::in(array_keys(AdoptionApplication::STATUSES))],
            'note' => ['nullable', 'string', 'max:5000', 'required_if:status,approved,completed,rejected,withdrawn'],
            'adopted_on' => ['exclude_unless:status,completed', 'required_if:status,completed', 'date_format:Y-m-d', 'before_or_equal:today'],
            'version' => [$this->route('adoption') ? 'required' : 'nullable', 'integer', 'min:1'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:10240'],
            'task_title' => ['nullable', 'string', 'max:160'],
            'task_due_date' => ['exclude_without:task_title', 'required_with:task_title', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['candidate_name' => 'imię i nazwisko kandydata', 'email' => 'adres e-mail', 'phone' => 'telefon', 'housing' => 'warunki i przygotowanie do adopcji', 'candidate_notes' => 'notatki', 'coordinator_id' => 'osoba prowadząca', 'status' => 'etap', 'note' => 'notatka / uzasadnienie', 'adopted_on' => 'data wydania', 'attachments' => 'dokumenty', 'attachments.*' => 'dokument', 'task_title' => 'zadanie', 'task_due_date' => 'termin zadania'];
    }
}
