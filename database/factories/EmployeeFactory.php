<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'employee_code' => 'EMP-'.fake()->unique()->numberBetween(1000, 999999),
            'first_name' => $first,
            'last_name' => $last,
            // ASCII email so seeding never depends on the intl extension to
            // transliterate Thai names (Faker safeEmail() throws without intl).
            'email' => 'staff'.fake()->unique()->numberBetween(100000, 999999).'@hrpro.local',
            'phone' => fake()->numerify('08########'),
            'national_id' => fake()->numerify('#############'),
            'date_of_birth' => fake()->dateTimeBetween('-55 years', '-22 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(Employee::GENDERS),
            'address' => fake()->address(),
            'employment_type' => fake()->randomElement(Employee::EMPLOYMENT_TYPES),
            'status' => fake()->randomElement(['active', 'active', 'active', 'probation']),
            'hire_date' => fake()->dateTimeBetween('-6 years', 'now')->format('Y-m-d'),
            'base_salary' => fake()->numberBetween(18000, 150000),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => fake()->numerify('08########'),
        ];
    }
}
