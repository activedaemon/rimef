<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrganizationType;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberProfile>
 */
class MemberProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'country_code' => fake()->randomElement(['SN', 'FR', 'CA', 'MA', 'BI', 'CH']),
            'organization_type' => fake()->randomElement(OrganizationType::cases()),
            'is_available' => false,
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => ['is_available' => true]);
    }

    /**
     * Langues parlées, dans l'ordre donné (codes ISO 639-1).
     */
    public function speaking(string ...$codes): static
    {
        return $this->afterCreating(fn (MemberProfile $profile) => $profile->syncLanguages(array_values($codes)));
    }
}
