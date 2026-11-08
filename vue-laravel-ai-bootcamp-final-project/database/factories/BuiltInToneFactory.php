<?php

namespace Database\Factories;

use App\Models\BuiltInTone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuiltInTone>
 */
class BuiltInToneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
