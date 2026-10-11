<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mes messages (GET /api/conversations) : recherche du bandeau et filtre « Non lues ».
 */
class ListConversationsRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'unread' => ['boolean'],
            'page' => ['integer', 'min:1'],
        ];
    }
}
