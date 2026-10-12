<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Fiche d'un événement (GET /api/events/{slug}) : le programme est découpé en jours dans le
 * fuseau de l'utilisatrice.
 */
class ShowEventRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['nullable', 'timezone:all'],
        ];
    }
}
