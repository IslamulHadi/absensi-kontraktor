<?php

namespace Database\Factories;

use App\Models\AttendanceLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceLocation>
 */
class AttendanceLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->streetName().' Site',
            'address' => fake()->address(),
            'latitude' => fake()->latitude(-11, 6),
            'longitude' => fake()->longitude(95, 141),
            'radius_meters' => fake()->randomElement([50, 100, 150, 200]),
            'is_active' => true,
            'is_default' => false,
        ];
    }

    public function companyDefault(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
