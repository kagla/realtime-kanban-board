<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'moved', 'deleted', 'assigned']),
            'target_type' => fake()->randomElement(['card', 'column', 'board']),
            'target_id' => fake()->numberBetween(1, 100),
            'metadata' => ['old' => fake()->word(), 'new' => fake()->word()],
        ];
    }
}
