<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Rôles d'un compte (PUT /api/admin/accounts/{user}/roles), superadmin seul.
 */
class UpdateAccountRolesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in([Role::Member->value, Role::Admin->value])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'roles.required' => 'Choisissez au moins un rôle.',
            'roles.min' => 'Choisissez au moins un rôle.',
        ];
    }
}
