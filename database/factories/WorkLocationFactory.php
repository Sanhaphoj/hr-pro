<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkLocation>
 */
class WorkLocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'สำนักงาน'.Str::title(fake()->unique()->word()),
            'code' => Str::upper(fake()->unique()->lexify('WL??')),
            'address' => fake()->address(),
            // Somewhere around Bangkok, close enough for the demo data to look
            // plausible on a map without pretending to be a real address.
            'latitude' => fake()->randomFloat(7, 13.65, 13.90),
            'longitude' => fake()->randomFloat(7, 100.40, 100.70),
            'radius_meters' => fake()->randomElement([100, 150, 200, 300]),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
