<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Jours d'un mois qui ont des événements (mini-calendrier, GET /api/events/calendar).
 */
class EventCalendarRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
            'timezone' => ['nullable', 'timezone:all'],
        ];
    }
}
