<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class UpdateTeamMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-team');
    }

    /** @return array<string, array<int, string|In>> */
    public function rules(): array
    {
        return ['role' => ['required', Rule::in(array_keys(User::TEAM_ROLES))], 'is_active' => ['required', 'boolean']];
    }

    public function attributes(): array
    {
        return ['role' => 'rola', 'is_active' => 'aktywność konta'];
    }
}
