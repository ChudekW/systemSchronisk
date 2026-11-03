<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class InviteTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-team');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => mb_strtolower(trim($this->input('email')))]);
        }
    }

    /** @return array<string, array<int, string|In>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(User::TEAM_ROLES))],
        ];
    }

    public function messages(): array
    {
        return ['email.unique' => 'Ten adres e-mail ma już konto. Zaproszenie wymaga nowego adresu.'];
    }

    public function attributes(): array
    {
        return ['name' => 'imię i nazwisko', 'email' => 'adres e-mail', 'role' => 'rola'];
    }
}
