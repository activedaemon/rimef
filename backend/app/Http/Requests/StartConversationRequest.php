<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Premier message à un compte (POST /api/conversations).
 */
class StartConversationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:'.SendMessageRequest::MAX_LENGTH],
        ];
    }
}
