<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Region;
use App\Enums\ResourceType;
use App\Models\Resource;
use App\Services\ResourceDirectory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Recherche, filtres, tri et pagination de la bibliothèque (GET /api/resources).
 */
class ListResourcesRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['array'],
            'type.*' => [Rule::enum(ResourceType::class)],
            'theme' => ['array', 'max:50'],
            'theme.*' => ['integer'],
            'region' => ['array'],
            'region.*' => [Rule::in([...array_column(Region::cases(), 'value'), Resource::WORLD_REGION])],
            'language' => ['array'],
            'language.*' => [Rule::in(array_keys(Resource::LANGUAGES))],
            'date' => ['nullable', 'regex:/^('.ResourceDirectory::RECENT.'|\d{4})$/'],
            'linked' => ['boolean'],
            'by_member' => ['boolean'],
            'with_event' => ['boolean'],
            'saved' => ['boolean'],
            'sort' => ['nullable', Rule::in(ResourceDirectory::SORTS)],
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', Rule::in(ResourceDirectory::PER_PAGE_OPTIONS)],
        ];
    }
}
