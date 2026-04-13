<?php

use App\Enums\AttendanceDayStatus;
use App\Filament\Resources\Attendances\Pages\CreateAttendance;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('admin can create attendance with clock in and clock out photos', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $employee = Employee::factory()->create();

    Livewire::actingAs($admin)
        ->test(CreateAttendance::class)
        ->fillForm([
            'employee_id' => $employee->id,
            'work_date' => now()->toDateString(),
            'status' => AttendanceDayStatus::Incomplete->value,
            'clock_in_photo' => [UploadedFile::fake()->image('in.jpg', 50, 50)],
            'clock_out_photo' => [UploadedFile::fake()->image('out.jpg', 50, 50)],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $attendance = Attendance::query()->where('employee_id', $employee->id)->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->getMedia(Attendance::MEDIA_CLOCK_IN))->toHaveCount(1)
        ->and($attendance->getMedia(Attendance::MEDIA_CLOCK_OUT))->toHaveCount(1);
});
