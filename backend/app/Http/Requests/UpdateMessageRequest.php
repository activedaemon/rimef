<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Modification d'un message (PATCH /api/conversations/{id}/messages/{message}).
 */
class UpdateMessageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.SendMessageRequest::MAX_LENGTH],
        ];
    }
}
