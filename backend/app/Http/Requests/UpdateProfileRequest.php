<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\OrganizationType;
use App\Services\ProfileEditor;
use App\Support\DirectoryCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Page « Modifier mon profil » (PUT /api/profile). Un compte sans fiche de médiatrice ne modifie
 * que son prénom et son nom : les autres champs ne sont pas retenus.
 */
class UpdateProfileRequest extends FormRequest
{
    public const MAX_EXPERTISES = 12;

    public const MAX_ZONES = 12;

    private const HTTPS_URL = ['nullable', 'string', 'max:255', 'url:https'];

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $identity = [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
        ];

        if (! app(ProfileEditor::class)->hasSheet($this->user())) {
            return $identity;
        }

        $countries = array_keys(DirectoryCatalog::countries());

        return $identity + [
            'job_title' => ['nullable', 'string', 'max:150'],
            'organization_type' => ['nullable', Rule::enum(OrganizationType::class)],
            'organization_name' => ['nullable', 'string', 'max:150'],
            'country_code' => ['nullable', Rule::in($countries)],
            'city' => ['nullable', 'string', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'experience_since' => ['nullable', 'integer', 'min:1950', 'max:'.now()->year],
            'audiences' => ['nullable', 'string', 'max:200'],
            'is_available' => ['boolean'],
            'expertises' => ['array', 'max:'.self::MAX_EXPERTISES],
            'expertises.*' => ['string', 'distinct', Rule::exists('expertises', 'name')],
            'zones' => ['array', 'max:'.self::MAX_ZONES],
            'zones.*' => ['string', 'distinct', Rule::in($countries)],
            'links' => ['array'],
            'links.linkedin' => [...self::HTTPS_URL, 'regex:#^https://([a-z]{2,3}\.)?linkedin\.com/#i'],
            'links.website' => self::HTTPS_URL,
            'links.organization' => self::HTTPS_URL,
            'links.other' => ['array'],
            'links.other.label' => ['nullable', 'string', 'max:40', 'required_with:links.other.url'],
            'links.other.url' => [...self::HTTPS_URL, 'required_with:links.other.label'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'links.linkedin.regex' => 'Cette adresse doit ressembler à https://www.linkedin.com/in/votre-nom.',
            'links.*.url' => 'Cette adresse ne semble pas valide. Vérifiez qu’elle commence par https://',
            'links.other.url.url' => 'Cette adresse ne semble pas valide. Vérifiez qu’elle commence par https://',
            'expertises.*.exists' => 'Ce domaine d’expertise ne fait pas partie de la liste du réseau.',
        ];
    }
}
