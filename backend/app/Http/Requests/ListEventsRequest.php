<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EventType;
use App\Enums\Region;
use App\Models\Event;
use App\Services\EventDirectory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Onglet, recherche, filtres et tri de l'Agenda (GET /api/events).
 */
class ListEventsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(EventDirectory::PERIODS)],
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['array'],
            'type.*' => [Rule::enum(EventType::class)],
            'region' => ['array'],
            'region.*' => [Rule::in([...array_column(Region::cases(), 'value'), Event::ONLINE_REGION])],
            'theme' => ['array', 'max:50'],
            'theme.*' => ['integer'],
            'month' => ['nullable', 'date_format:Y-m'],
            'day' => ['nullable', 'date_format:Y-m-d'],
            'participating' => ['boolean'],
            'online' => ['boolean'],
            'crowd' => ['boolean'],
            'sort' => ['nullable', Rule::in(['date', 'attendees'])],
            'page' => ['integer', 'min:1'],
            // Fuseau de l'utilisatrice, pour les mois et les jours
            'timezone' => ['nullable', 'timezone:all'],
        ];
    }
}
