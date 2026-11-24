<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'Est-ce que '.fake()->sentence(6),
            'description' => fake()->paragraph(3),
            'category' => fake()->randomElement(array_keys(Event::CATEGORIES)),
            'closes_at' => now()->addDays(fake()->numberBetween(2, 30)),
            'status' => Event::STATUS_OPEN,
            'odds_yes' => 2.00,
            'odds_no' => 2.00,
        ];
    }
}
