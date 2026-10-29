<?php

namespace App\Http\Requests\Admin;

use App\Enums\MembershipRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_central_admin' => ['nullable', 'boolean'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'church_role' => ['nullable', 'string', Rule::enum(MembershipRole::class)],
            'email_verified' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}

