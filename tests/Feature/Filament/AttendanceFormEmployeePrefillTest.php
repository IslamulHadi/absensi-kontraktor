<?php

use App\Filament\Resources\Attendances\Pages\CreateAttendance;
use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

test('selecting employee prefills attendance location and coordinates from primary assignment', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    $primary = AttendanceLocation::factory()->create([
        'name' => 'Lokasi utama',
        'latitude' => -6.20000000,
        'longitude' => 106.81666600,
    ]);
    $other = AttendanceLocation::factory()->create(['name' => 'Lokasi lain']);

    $employee->attendanceLocations()->attach($other->id, ['is_primary' => false]);
    $employee->attendanceLocations()->attach($primary->id, ['is_primary' => true]);

    $livewire = Livewire::actingAs($admin)
        ->test(CreateAttendance::class)
        ->fillForm(['employee_id' => $employee->id])
        ->assertFormSet([
            'attendance_location_id' => $primary->id,
        ]);

    $raw = $livewire->instance()->form->getRawState();

    expect((float) $raw['clock_in_latitude'])->toBe(-6.2)
        ->and((float) $raw['clock_in_longitude'])->toBe(106.816666)
        ->and((float) $raw['clock_out_latitude'])->toBe(-6.2)
        ->and((float) $raw['clock_out_longitude'])->toBe(106.816666);
});

test('selecting employee without assignment prefills company default location', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    $default = AttendanceLocation::factory()->companyDefault()->create([
        'latitude' => -7.25000000,
        'longitude' => 112.75000000,
    ]);

    $livewire = Livewire::actingAs($admin)
        ->test(CreateAttendance::class)
        ->fillForm(['employee_id' => $employee->id])
        ->assertFormSet([
            'attendance_location_id' => $default->id,
        ]);

    $raw = $livewire->instance()->form->getRawState();

    expect((float) $raw['clock_in_latitude'])->toBe(-7.25)
        ->and((float) $raw['clock_in_longitude'])->toBe(112.75);
});
