<?php

namespace Database\Factories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Position>
 */
class PositionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'code' => Str::upper(fake()->unique()->bothify('POS-###')),
            'level' => fake()->randomElement(Position::LEVELS),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
