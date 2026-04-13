<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'nik' => fake()->unique()->numerify('##########'),
            'full_name' => fake()->name(),
            'phone' => fake()->optional()->e164PhoneNumber(),
            'department' => fake()->optional()->company(),
            'position' => fake()->optional()->jobTitle(),
            'is_active' => true,
            'hired_at' => fake()->optional()->date(),
            'notes' => null,
        ];
    }

    public function withUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }
}
