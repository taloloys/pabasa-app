<?php

namespace App\Http\Requests\Admin;

use App\Enums\MembershipRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $this->user()?->can('manageRoles', $user) ?? false;
    }

    public function rules(): array
    {
        return [
            'role_type' => ['required', 'string', 'in:central_admin,church_membership'],
            'is_central_admin' => ['nullable', 'boolean'],
            'church_id' => ['required_if:role_type,church_membership', 'nullable', 'integer', 'exists:churches,id'],
            'role' => ['required_if:role_type,church_membership', 'nullable', 'string', Rule::enum(MembershipRole::class)],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
