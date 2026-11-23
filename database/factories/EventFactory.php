<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'title' => 'Est-ce que '.fake()->sentence(6),
            'description' => fake()->paragraph(3),
            'category' => fake()->randomElement(array_keys(Event::CATEGORIES)),
            'closes_at' => now()->addDays(fake()->numberBetween(2, 30)),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Event $event) {
            $event->status ??= EventStatus::Open;
            $event->user_id ??= User::factory()->create()->id;
        });
    }

    public function pending(): static
    {
        return $this->afterMaking(fn (Event $event) => $event->status = EventStatus::Pending);
    }

    public function odds(float $yes, float $no): static
    {
        return $this->afterMaking(function (Event $event) use ($yes, $no) {
            $event->odds_yes = $yes;
            $event->odds_no = $no;
        });
    }
}
