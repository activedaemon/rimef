<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'type' => fake()->randomElement(EventType::cases()),
            'starts_at' => now()->addWeeks(2),
            'city' => fake()->city(),
            'country_code' => 'FR',
        ];
    }

    /**
     * Événement en ligne (sans ville ni pays).
     */
    public function online(): static
    {
        return $this->state(fn (): array => ['city' => null, 'country_code' => null, 'is_online' => true]);
    }
}
