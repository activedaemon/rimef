<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ContactSubject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Fenêtre « Contacter » d'une fiche (POST /api/members/{slug}/contact).
 */
class ContactMemberRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', Rule::enum(ContactSubject::class)],
            'body' => ['required', 'string', 'max:'.SendMessageRequest::MAX_LENGTH],
        ];
    }
}
