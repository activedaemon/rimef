<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\OrganizationType;
use App\Enums\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Recherche, filtres et tri de l'annuaire (GET /api/members).
 */
class ListMembersRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'expertise' => ['array', 'max:50'],
            'expertise.*' => ['integer'],
            'region' => ['array'],
            'region.*' => [Rule::enum(Region::class)],
            'organization' => ['array'],
            'organization.*' => [Rule::enum(OrganizationType::class)],
            'available' => ['boolean'],
            'favorites' => ['boolean'],
            'upcoming' => ['boolean'],
            'sort' => ['nullable', Rule::in(['name', 'country', 'event'])],
            'page' => ['integer', 'min:1'],
        ];
    }
}
