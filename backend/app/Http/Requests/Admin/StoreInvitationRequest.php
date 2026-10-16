<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Invitation d'un nouveau compte (POST /api/admin/invitations).
 *
 * Une administratrice invite toujours une membre ; seul le superadmin choisit les rôles
 * (Membre et/ou Administratrice).
 */
class StoreInvitationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'roles' => ['array'],
            'roles.*' => [Rule::in([Role::Member->value, Role::Admin->value])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse est déjà utilisée par un compte.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->isSuperAdmin() && $this->filled('roles') === false) {
                    $validator->errors()->add('roles', 'Choisissez au moins un rôle.');
                }
            },
        ];
    }

    /**
     * Rôles du compte invité : ceux cochés par le superadmin, Membre pour une administratrice.
     *
     * @return list<Role>
     */
    public function invitedRoles(): array
    {
        if (! $this->isSuperAdmin()) {
            return [Role::Member];
        }

        return array_values(array_map(Role::from(...), array_unique($this->validated('roles', []))));
    }

    private function isSuperAdmin(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->isSuperAdmin();
    }
}
