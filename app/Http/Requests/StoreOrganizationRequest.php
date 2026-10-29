<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-organizations');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['admin_email' => strtolower(trim((string) $this->input('admin_email')))]);
    }

    /** @return array<string, array<int, string|Password>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'shelter_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', Password::min(12)->mixedCase()->numbers(), 'confirmed'],
        ];
    }
}
