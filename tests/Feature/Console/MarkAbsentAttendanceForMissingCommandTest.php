<?php

use App\Enums\AttendanceDayStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Carbon;

test('command inserts absent rows for active employees without attendance', function () {
    $day = '2026-04-10';
    Employee::factory()->count(2)->create(['is_active' => true]);

    $this->artisan('attendance:mark-absent-for-missing', ['--date' => $day])
        ->assertSuccessful();

    expect(Attendance::query()->where('work_date', $day)->count())->toBe(2)
        ->and(
            Attendance::query()->where('work_date', $day)->where('status', AttendanceDayStatus::Absent)->count()
        )->toBe(2);
});

test('command skips inactive employees', function () {
    $day = '2026-04-11';
    Employee::factory()->create(['is_active' => false]);

    $this->artisan('attendance:mark-absent-for-missing', ['--date' => $day])
        ->assertSuccessful();

    expect(Attendance::query()->where('work_date', $day)->count())->toBe(0);
});

test('command does not overwrite existing attendance for the work date', function () {
    $day = '2026-04-12';
    $withRow = Employee::factory()->create(['is_active' => true]);
    Attendance::factory()->create([
        'employee_id' => $withRow->id,
        'work_date' => $day,
        'status' => AttendanceDayStatus::Incomplete,
        'clock_in_at' => now(),
    ]);
    Employee::factory()->create(['is_active' => true]);

    $this->artisan('attendance:mark-absent-for-missing', ['--date' => $day])
        ->assertSuccessful();

    expect(Attendance::query()->where('work_date', $day)->count())->toBe(2);

    $existing = Attendance::query()
        ->where('employee_id', $withRow->id)
        ->where('work_date', $day)
        ->first();

    expect($existing)->not->toBeNull()
        ->and($existing->status)->toBe(AttendanceDayStatus::Incomplete);
});

test('command is idempotent for the same work date', function () {
    $day = '2026-04-13';
    Employee::factory()->create(['is_active' => true]);

    $this->artisan('attendance:mark-absent-for-missing', ['--date' => $day])
        ->assertSuccessful();
    $this->artisan('attendance:mark-absent-for-missing', ['--date' => $day])
        ->assertSuccessful();

    expect(Attendance::query()->where('work_date', $day)->count())->toBe(1);
});

test('command rejects invalid date option', function () {
    $this->artisan('attendance:mark-absent-for-missing', ['--date' => 'not-a-date'])
        ->assertFailed();
});

test('command uses day-close timezone for default work date', function () {
    config(['attendance.day_close_timezone' => 'Asia/Jakarta']);
    Carbon::setTestNow(Carbon::parse('2026-06-15 21:00:00', 'Asia/Jakarta'));

    try {
        Employee::factory()->create(['is_active' => true]);

        $this->artisan('attendance:mark-absent-for-missing')->assertSuccessful();

        expect(Attendance::query()->where('work_date', '2026-06-15')->count())->toBe(1);
    } finally {
        Carbon::setTestNow();
    }
});
