<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Réponse dans une conversation (POST /api/conversations/{id}/messages).
 */
class SendMessageRequest extends FormRequest
{
    public const MAX_LENGTH = 2000;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.self::MAX_LENGTH],
        ];
    }
}
