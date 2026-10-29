<?php

namespace App\Http\Requests;

use App\Models\Animal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Unique;

class SaveAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->route('animal') instanceof Animal) {
            Gate::authorize('access-animal', $this->route('animal'));
        }

        return $this->user()->can('manage-animals');
    }

    /** @return array<string, array<int, string|In|Unique>> */
    public function rules(): array
    {
        $animal = $this->route('animal');
        $uniqueNumber = Rule::unique('animals', 'registration_number')->where('organization_id', $this->user()->organization_id);
        $uniqueChip = Rule::unique('animals', 'microchip')->where('organization_id', $this->user()->organization_id);
        if ($animal instanceof Animal) {
            $uniqueNumber->ignore($animal);
            $uniqueChip->ignore($animal);
        }

        return [
            'registration_number' => ['required', 'string', 'max:80', $uniqueNumber],
            'name' => ['required', 'string', 'max:120'],
            'species' => ['required', Rule::in(array_keys(Animal::SPECIES))],
            'breed' => ['nullable', 'string', 'max:120'],
            'sex' => ['required', Rule::in(array_keys(Animal::SEXES))],
            'birth_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'before_or_equal:admitted_at'],
            'birth_date_estimated' => ['required', 'boolean'],
            'color' => ['nullable', 'string', 'max:120'],
            'weight_kg' => ['nullable', 'numeric', 'gt:0', 'max:9999'],
            'microchip' => ['nullable', 'regex:/^[0-9]{9,15}$/', $uniqueChip],
            'admitted_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'admission_reason' => ['required', Rule::in(array_keys(Animal::ADMISSION_REASONS))],
            'found_location' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(array_keys(Animal::STATUSES))],
            'sterilization_status' => ['required', Rule::in(array_keys(Animal::STERILIZATION))],
            'health_notes' => ['nullable', 'string', 'max:10000'],
            'behavior_notes' => ['nullable', 'string', 'max:10000'],
            'care_notes' => ['nullable', 'string', 'max:10000'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status_note' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=6000,max_height=6000'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'registration_number' => 'numer ewidencyjny', 'name' => 'imię', 'species' => 'gatunek', 'breed' => 'rasa', 'sex' => 'płeć',
            'birth_date' => 'data urodzenia', 'birth_date_estimated' => 'szacunkowa data urodzenia', 'color' => 'umaszczenie', 'weight_kg' => 'masa ciała',
            'microchip' => 'numer mikroczipa', 'admitted_at' => 'data przyjęcia', 'admission_reason' => 'powód przyjęcia', 'found_location' => 'miejsce znalezienia',
            'location' => 'lokalizacja w schronisku', 'status' => 'status', 'sterilization_status' => 'kastracja / sterylizacja',
            'health_notes' => 'informacje o zdrowiu', 'behavior_notes' => 'charakter i zachowanie', 'care_notes' => 'zalecenia opieki',
            'description' => 'opis', 'status_note' => 'uzasadnienie zmiany statusu', 'photo' => 'zdjęcie', 'remove_photo' => 'usunięcie zdjęcia',
        ];
    }
}
