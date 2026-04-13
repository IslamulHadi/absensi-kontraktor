<?php

use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin can view filament attendance record with clock in photo infolist', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();
    $location = AttendanceLocation::factory()->create();

    $attendance = Attendance::factory()->create([
        'employee_id' => $employee->id,
        'attendance_location_id' => $location->id,
    ]);

    $attendance->addMedia(UploadedFile::fake()->image('clock-in.jpg'))
        ->toMediaCollection(Attendance::MEDIA_CLOCK_IN);

    $url = AttendanceResource::getUrl('view', ['record' => $attendance], panel: 'admin');

    $this->actingAs($admin)
        ->get($url)
        ->assertOk()
        ->assertSee('Foto absensi')
        ->assertSee('Foto masuk');
});
