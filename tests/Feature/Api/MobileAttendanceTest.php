<?php

use App\Enums\AttendanceDayStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\Employee;
use App\Models\User;
use App\Support\AttendancePhotoOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('employee can list assigned attendance locations', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    $employee = Employee::factory()->for($user)->create(['is_active' => true]);
    $location = AttendanceLocation::factory()->create(['is_active' => true]);
    $employee->attendanceLocations()->attach($location, ['is_primary' => true]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/me/attendance-locations');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.from_company_default', false);
});

test('employee without assigned location gets company default location in list', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    Employee::factory()->for($user)->create(['is_active' => true]);
    $defaultLoc = AttendanceLocation::factory()->companyDefault()->create([
        'is_active' => true,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_meters' => 500,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/me/attendance-locations');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $defaultLoc->id)
        ->assertJsonPath('data.0.from_company_default', true);
});

test('employee without assigned location and no default gets empty list', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    Employee::factory()->for($user)->create(['is_active' => true]);
    AttendanceLocation::factory()->create(['is_active' => true, 'is_default' => false]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/me/attendance-locations');

    $response->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

test('employee can clock in with photo stored as spatie media', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    $employee = Employee::factory()->for($user)->create(['is_active' => true]);
    $location = AttendanceLocation::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_meters' => 500,
        'is_active' => true,
    ]);
    $employee->attendanceLocations()->attach($location, ['is_primary' => true]);

    $token = $user->createToken('test')->plainTextToken;
    $photo = UploadedFile::fake()->image('in.jpg', 120, 120);

    $response = $this->withToken($token)->post('/api/v1/me/attendances/clock-in', [
        'attendance_location_id' => $location->id,
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'photo' => $photo,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Absen masuk berhasil.')
        ->assertJsonStructure(['attendance' => ['clock_in_photo_url', 'id']]);

    $attendance = Attendance::query()->first();
    expect($attendance)->not->toBeNull()
        ->and($attendance->getMedia(Attendance::MEDIA_CLOCK_IN))->toHaveCount(1);

    $stored = $attendance->getFirstMedia(Attendance::MEDIA_CLOCK_IN);
    expect($stored)->not->toBeNull()
        ->and((int) $stored->size)->toBeLessThanOrEqual(AttendancePhotoOptimizer::MAX_BYTES);
});

test('clock in is rejected outside location radius', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    $employee = Employee::factory()->for($user)->create(['is_active' => true]);
    $location = AttendanceLocation::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_meters' => 10,
        'is_active' => true,
    ]);
    $employee->attendanceLocations()->attach($location, ['is_primary' => true]);

    $token = $user->createToken('test')->plainTextToken;
    $photo = UploadedFile::fake()->image('in.jpg', 80, 80);

    $response = $this->withToken($token)->post('/api/v1/me/attendances/clock-in', [
        'attendance_location_id' => $location->id,
        'latitude' => '-7.0',
        'longitude' => '108.0',
        'photo' => $photo,
    ]);

    $response->assertUnprocessable();
    expect(Attendance::query()->count())->toBe(0);
});

test('employee can clock in using company default location when not assigned', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    Employee::factory()->for($user)->create(['is_active' => true]);
    $location = AttendanceLocation::factory()->companyDefault()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_meters' => 500,
        'is_active' => true,
    ]);

    $token = $user->createToken('test')->plainTextToken;
    $photo = UploadedFile::fake()->image('in.jpg', 120, 120);

    $response = $this->withToken($token)->post('/api/v1/me/attendances/clock-in', [
        'attendance_location_id' => $location->id,
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'photo' => $photo,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Absen masuk berhasil.');
});

test('clock in is rejected when location id is not the resolved single location', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    $employee = Employee::factory()->for($user)->create(['is_active' => true]);
    $assigned = AttendanceLocation::factory()->create(['is_active' => true]);
    $other = AttendanceLocation::factory()->create(['is_active' => true]);
    $employee->attendanceLocations()->attach($assigned, ['is_primary' => true]);
    $employee->attendanceLocations()->attach($other, ['is_primary' => false]);

    $token = $user->createToken('test')->plainTextToken;
    $photo = UploadedFile::fake()->image('in.jpg', 120, 120);

    $response = $this->withToken($token)->post('/api/v1/me/attendances/clock-in', [
        'attendance_location_id' => $other->id,
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'photo' => $photo,
    ]);

    $response->assertUnprocessable();
    expect(Attendance::query()->count())->toBe(0);
});

test('clock in with client_request_id is idempotent on retry', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    $employee = Employee::factory()->for($user)->create(['is_active' => true]);
    $location = AttendanceLocation::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_meters' => 500,
        'is_active' => true,
    ]);
    $employee->attendanceLocations()->attach($location, ['is_primary' => true]);

    $token = $user->createToken('test')->plainTextToken;
    $payload = [
        'attendance_location_id' => $location->id,
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'photo' => UploadedFile::fake()->image('in.jpg', 120, 120),
        'client_request_id' => 'test-uuid-clock-in-1',
        'client_recorded_at' => now()->subHour()->toIso8601String(),
    ];

    $this->withToken($token)->post('/api/v1/me/attendances/clock-in', $payload)->assertSuccessful();

    $second = $this->withToken($token)->post('/api/v1/me/attendances/clock-in', $payload);
    $second->assertSuccessful()
        ->assertJsonPath('message', 'Absen masuk berhasil.');

    expect(Attendance::query()->count())->toBe(1);
});

test('clock out accepts work_date to resolve open attendance after midnight', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    $employee = Employee::factory()->for($user)->create(['is_active' => true]);
    $location = AttendanceLocation::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_meters' => 500,
        'is_active' => true,
    ]);
    $employee->attendanceLocations()->attach($location, ['is_primary' => true]);

    $yesterday = now()->subDay()->startOfDay();

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'attendance_location_id' => $location->id,
        'work_date' => $yesterday->toDateString(),
        'clock_in_at' => $yesterday->copy()->setTime(8, 0),
        'clock_out_at' => null,
        'status' => AttendanceDayStatus::Incomplete,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->post('/api/v1/me/attendances/clock-out', [
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'photo' => UploadedFile::fake()->image('out.jpg'),
        'work_date' => $yesterday->toDateString(),
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('attendance.status', 'present');

    $row = Attendance::query()->where('employee_id', $employee->id)->first();
    expect($row->clock_out_at)->not->toBeNull();
});

test('clock in is rejected for strict employee with mock GPS flag', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    $employee = Employee::factory()->for($user)->create([
        'is_active' => true,
        'is_attendance_strict' => true,
    ]);
    $location = AttendanceLocation::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_meters' => 500,
        'is_active' => true,
    ]);
    $employee->attendanceLocations()->attach($location);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->post('/api/v1/me/attendances/clock-in', [
        'attendance_location_id' => $location->id,
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'photo' => UploadedFile::fake()->image('in.jpg', 120, 120),
        'is_mock_location' => true,
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'Mock GPS terdeteksi. Nonaktifkan aplikasi pemalsu lokasi sebelum absen.');
    expect(Attendance::query()->count())->toBe(0);
});

test('clock in is allowed for non-strict employee even with mock GPS flag', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    $employee = Employee::factory()->for($user)->create([
        'is_active' => true,
        'is_attendance_strict' => false,
    ]);
    $location = AttendanceLocation::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_meters' => 500,
        'is_active' => true,
    ]);
    $employee->attendanceLocations()->attach($location);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->post('/api/v1/me/attendances/clock-in', [
        'attendance_location_id' => $location->id,
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'photo' => UploadedFile::fake()->image('in.jpg', 120, 120),
        'is_mock_location' => true,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Absen masuk berhasil.');
});

test('clock in rejects client_recorded_at older than 48 hours', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    $employee = Employee::factory()->for($user)->create(['is_active' => true]);
    $location = AttendanceLocation::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_meters' => 500,
        'is_active' => true,
    ]);
    $employee->attendanceLocations()->attach($location);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/me/attendances/clock-in', [
        'attendance_location_id' => $location->id,
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'photo' => UploadedFile::fake()->image('in.jpg', 120, 120),
        'client_recorded_at' => now()->subHours(72)->toIso8601String(),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['client_recorded_at']);
    expect(Attendance::query()->count())->toBe(0);
});

test('employee can clock out after clock in with second photo', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => UserRole::Employee]);
    $employee = Employee::factory()->for($user)->create(['is_active' => true]);
    $location = AttendanceLocation::factory()->create([
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius_meters' => 500,
        'is_active' => true,
    ]);
    $employee->attendanceLocations()->attach($location, ['is_primary' => true]);

    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->post('/api/v1/me/attendances/clock-in', [
        'attendance_location_id' => $location->id,
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'photo' => UploadedFile::fake()->image('in.jpg'),
    ])->assertSuccessful();

    $response = $this->withToken($token)->post('/api/v1/me/attendances/clock-out', [
        'latitude' => '-6.2',
        'longitude' => '106.8',
        'photo' => UploadedFile::fake()->image('out.jpg'),
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('attendance.status', 'present');

    $attendance = Attendance::query()->first();
    expect($attendance->getMedia(Attendance::MEDIA_CLOCK_IN))->toHaveCount(1)
        ->and($attendance->getMedia(Attendance::MEDIA_CLOCK_OUT))->toHaveCount(1);
});
