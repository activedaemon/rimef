<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Services\AccountDirectory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Recherche, filtres et tri de la liste des comptes (GET /api/admin/accounts).
 */
class ListAccountsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['array'],
            'status.*' => [Rule::enum(AccountStatus::class)],
            'role' => ['array'],
            'role.*' => [Rule::enum(Role::class)],
            'sort' => ['nullable', Rule::in(['name', 'created', 'last_login', 'completion'])],
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', Rule::in(AccountDirectory::PER_PAGE_OPTIONS)],
        ];
    }
}
