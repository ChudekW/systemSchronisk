<?php

namespace App\Http\Requests;

use App\Models\Animal;
use App\Models\MedicalRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\In;

class StoreMedicalRecordRequest extends FormRequest
{
    protected function getRedirectUrl(): string
    {
        return route('animals.health.index', ['animal' => $this->route('animal')]);
    }

    public function authorize(): bool
    {
        $animal = $this->route('animal');
        abort_unless($animal instanceof Animal, 404);
        Gate::authorize('access-animal', $animal);
        if ($this->input('kind') !== 'observation') {
            Gate::authorize('manage-medical');
        }

        return true;
    }

    /** @return array<string, array<int, string|Exists|In>> */
    public function rules(): array
    {
        $medical = $this->input('kind') !== 'observation';

        return [
            'kind' => ['required', Rule::in(array_keys(MedicalRecord::KINDS))],
            'title' => ['required', 'string', 'max:160'],
            'occurred_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:10000'],
            'performed_by' => [$medical ? 'required' : 'prohibited', 'nullable', 'string', 'max:255'],
            'recommendations' => [$medical ? 'nullable' : 'prohibited', 'nullable', 'string', 'max:10000'],
            'weight_kg' => [$medical ? 'nullable' : 'prohibited', 'nullable', 'required_if:kind,weight', 'numeric', 'min:0.001', 'max:5000', 'decimal:0,3'],
            'correction_of_id' => ['nullable', 'integer'],
            'correction_reason' => ['nullable', 'required_with:correction_of_id', 'string', 'max:2000'],
            'next_due_date' => [$medical ? 'nullable' : 'prohibited', 'nullable', 'date_format:Y-m-d', 'after_or_equal:today', 'after_or_equal:occurred_on'],
            'assignee_id' => ['exclude_without:next_due_date', 'required_with:next_due_date', 'integer', Rule::exists('users', 'id')->where('organization_id', $this->user()->organization_id)->where('is_active', true)->where('is_super_admin', false)],
            'attachments' => ['nullable', 'array', 'max:3'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['kind' => 'rodzaj wpisu', 'title' => 'tytuł', 'occurred_on' => 'data czynności', 'description' => 'opis', 'performed_by' => 'osoba wykonująca', 'recommendations' => 'zalecenia', 'weight_kg' => 'masa ciała', 'correction_reason' => 'powód korekty', 'next_due_date' => 'kolejny termin', 'assignee_id' => 'osoba odpowiedzialna', 'attachments' => 'załączniki', 'attachments.*' => 'załącznik'];
    }
}
