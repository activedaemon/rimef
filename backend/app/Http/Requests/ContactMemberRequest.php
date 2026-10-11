<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Fenêtres « Contacter » et « Nouveau message » (POST /api/members/{slug}/contact).
 */
class ContactMemberRequest extends FormRequest
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
