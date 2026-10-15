<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\ProfileEditor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

/**
 * Photo du profil (POST /api/profile/photo), réservée aux comptes qui ont une fiche de médiatrice.
 */
class UpdateProfilePhotoRequest extends FormRequest
{
    public const MAX_KILOBYTES = 5 * 1024;

    public function authorize(): bool
    {
        return app(ProfileEditor::class)->hasSheet($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max(self::MAX_KILOBYTES)
                    ->dimensions(Rule::dimensions()->minWidth(ProfileEditor::PHOTO_SIZE)->minHeight(ProfileEditor::PHOTO_SIZE)),
            ],
        ];
    }
}
