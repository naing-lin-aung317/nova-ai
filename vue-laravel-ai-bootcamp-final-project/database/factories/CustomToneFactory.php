<?php

namespace Database\Factories;

use App\Models\CustomTone;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomTone>
 */
class CustomToneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'sample_content' => fake()->paragraph(),
        ];
    }
}
