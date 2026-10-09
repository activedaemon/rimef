<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Expertise;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expertise>
 */
class ExpertiseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
        ];
    }
}
