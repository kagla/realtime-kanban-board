<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Column;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'column_id' => Column::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.7)->paragraph(),
            'assigned_user_id' => null,
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'position' => 0,
            'due_date' => fake()->optional(0.5)->dateTimeBetween('now', '+30 days'),
        ];
    }
}
