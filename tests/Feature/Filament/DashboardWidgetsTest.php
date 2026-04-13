<?php

use App\Enums\AttendanceDayStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;

test('admin dashboard shows attendance summary and recent records', function () {
    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create(['full_name' => 'Tester Dashboard Pegawai']);
    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'work_date' => now()->toDateString(),
        'status' => AttendanceDayStatus::Present,
    ]);

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('Ringkasan absensi')
        ->assertSee('Tester Dashboard Pegawai')
        ->assertSee('Absensi terbaru');
});
