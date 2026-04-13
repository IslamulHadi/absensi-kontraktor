<?php

namespace Database\Factories;

use App\Enums\AttendanceDayStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workDate = fake()->dateTimeBetween('-1 month', 'now');

        return [
            'employee_id' => Employee::factory(),
            'attendance_location_id' => null,
            'work_date' => $workDate,
            'clock_in_at' => null,
            'clock_out_at' => null,
            'clock_in_latitude' => null,
            'clock_in_longitude' => null,
            'clock_out_latitude' => null,
            'clock_out_longitude' => null,
            'status' => AttendanceDayStatus::Incomplete,
            'notes' => null,
        ];
    }
}
