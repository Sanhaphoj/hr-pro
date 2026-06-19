<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => Str::title($name),
            'code' => Str::upper(fake()->unique()->lexify('LT??')),
            'days_per_year' => fake()->randomElement([6, 10, 15, 30]),
            'requires_approval' => true,
            'is_paid' => true,
            'color' => fake()->randomElement(['blue', 'green', 'amber', 'red', 'gray']),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
