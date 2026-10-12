<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\EventDirectory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valeurs des filtres de l'Agenda pour un onglet (GET /api/events/filters).
 */
class EventFiltersRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(EventDirectory::PERIODS)],
            'timezone' => ['nullable', 'timezone:all'],
        ];
    }
}
