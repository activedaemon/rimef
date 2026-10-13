<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Resource>
 */
class ResourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(ResourceType::cases()),
            'title' => fake()->sentence(4),
            'source' => fake()->company(),
            'published_on' => now()->subMonth()->toDateString(),
            'languages' => ['fr'],
        ];
    }
}
