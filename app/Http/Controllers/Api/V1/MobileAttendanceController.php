<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceDayStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ClockInAttendanceRequest;
use App\Http\Requests\Api\V1\ClockOutAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Support\AttendancePhotoOptimizer;
use App\Support\Geo;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MobileAttendanceController extends Controller
{
    public function attendanceLocations(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        [$locations, $fromCompanyDefault] = $employee->resolveMobileAttendanceLocations();
        if ($locations === []) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => collect($locations)
                ->map(fn (AttendanceLocation $location): array => $this->mobileLocationPayload($location, $fromCompanyDefault))
                ->all(),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $paginator = Attendance::query()
            ->where('employee_id', $employee->id)
            ->with(['attendanceLocation', 'clockOutAttendanceLocation', 'media'])
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json(
            $paginator->through(fn (Attendance $attendance) => $this->attendancePayload($attendance))
        );
    }

    public function today(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', now()->startOfDay())
            ->with(['attendanceLocation', 'clockOutAttendanceLocation', 'media'])
            ->first();

        return response()->json([
            'attendance' => $attendance ? $this->attendancePayload($attendance) : null,
        ]);
    }

    public function clockIn(ClockInAttendanceRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if ($employee->is_attendance_strict && $request->boolean('is_mock_location')) {
            return response()->json([
                'message' => 'Mock GPS terdeteksi. Nonaktifkan aplikasi pemalsu lokasi sebelum absen.',
            ], 422);
        }

        $recordedAt = $this->parseClientRecordedAt($request->validated('client_recorded_at'));

        $clientRequestId = $request->validated('client_request_id');
        if (is_string($clientRequestId) && $clientRequestId !== '') {
            $dup = Attendance::query()
                ->where('employee_id', $employee->id)
                ->where('client_clock_in_request_id', $clientRequestId)
                ->with(['attendanceLocation', 'clockOutAttendanceLocation', 'media'])
                ->first();
            if ($dup !== null) {
                return response()->json([
                    'message' => 'Absen masuk berhasil.',
                    'attendance' => $this->attendancePayload($dup),
                ]);
            }
        }

        $workDate = $recordedAt->copy()->timezone(config('app.timezone'))->startOfDay()->toDateString();

        $location = AttendanceLocation::query()
            ->whereKey($request->validated('attendance_location_id'))
            ->where('is_active', true)
            ->firstOrFail();

        $distance = Geo::distanceMeters(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            (float) $location->latitude,
            (float) $location->longitude,
        );

        if ($employee->is_attendance_strict && $distance > $location->radius_meters + 25) {
            return response()->json([
                'message' => 'Lokasi GPS di luar radius yang diizinkan.',
            ], 422);
        }

        $attendance = Attendance::firstOrNew([
            'employee_id' => $employee->id,
            'work_date' => $workDate,
        ]);

        if ($attendance->exists && $attendance->clock_in_at !== null) {
            return response()->json([
                'message' => 'Sudah absen masuk hari ini.',
            ], 422);
        }

        $attendance->employee_id = $employee->id;
        $attendance->work_date = $workDate;
        $attendance->attendance_location_id = $location->id;
        $attendance->clock_in_at = $recordedAt;
        [$clockInLat, $clockInLon] = $this->resolvedStoredCoordinates(
            $employee->is_attendance_strict,
            $location,
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        );
        $attendance->clock_in_latitude = $clockInLat;
        $attendance->clock_in_longitude = $clockInLon;
        $attendance->status = AttendanceDayStatus::Incomplete;
        if (is_string($clientRequestId) && $clientRequestId !== '') {
            $attendance->client_clock_in_request_id = $clientRequestId;
        }
        $attendance->save();

        $this->storeOptimizedAttendancePhoto(
            $attendance,
            $request->file('photo'),
            Attendance::MEDIA_CLOCK_IN,
        );

        $attendance->load(['attendanceLocation', 'clockOutAttendanceLocation', 'media']);

        return response()->json([
            'message' => 'Absen masuk berhasil.',
            'attendance' => $this->attendancePayload($attendance),
        ]);
    }

    public function clockOut(ClockOutAttendanceRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        if ($employee->is_attendance_strict && $request->boolean('is_mock_location')) {
            return response()->json([
                'message' => 'Mock GPS terdeteksi. Nonaktifkan aplikasi pemalsu lokasi sebelum absen.',
            ], 422);
        }

        $recordedAt = $this->parseClientRecordedAt($request->validated('client_recorded_at'));

        $clientRequestId = $request->validated('client_request_id');
        if (is_string($clientRequestId) && $clientRequestId !== '') {
            $dup = Attendance::query()
                ->where('employee_id', $employee->id)
                ->where('client_clock_out_request_id', $clientRequestId)
                ->with(['attendanceLocation', 'clockOutAttendanceLocation', 'media'])
                ->first();
            if ($dup !== null) {
                return response()->json([
                    'message' => 'Absen pulang berhasil.',
                    'attendance' => $this->attendancePayload($dup),
                ]);
            }
        }

        $workDateFilter = $request->validated('work_date');

        $query = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereNotNull('clock_in_at')
            ->whereNull('clock_out_at')
            ->with(['attendanceLocation', 'clockOutAttendanceLocation']);

        if ($workDateFilter !== null) {
            $query->whereDate('work_date', $workDateFilter);
        } else {
            $query->whereDate('work_date', now()->timezone(config('app.timezone'))->toDateString());
        }

        $attendance = $query->first();

        if ($attendance === null || $attendance->clock_in_at === null) {
            return response()->json([
                'message' => 'Belum ada absen masuk hari ini.',
            ], 422);
        }

        if ($attendance->clock_out_at !== null) {
            return response()->json([
                'message' => 'Sudah absen pulang hari ini.',
            ], 422);
        }

        $requestedCheckoutLocationId = $request->validated('attendance_location_id');
        $checkoutLocation = null;
        if ($requestedCheckoutLocationId !== null) {
            $checkoutLocation = AttendanceLocation::query()
                ->whereKey($requestedCheckoutLocationId)
                ->where('is_active', true)
                ->first();
            if ($checkoutLocation === null) {
                return response()->json([
                    'message' => 'Lokasi absensi untuk pulang tidak ditemukan atau tidak aktif.',
                ], 422);
            }
        } else {
            $checkoutLocation = $attendance->attendanceLocation;
        }

        if ($checkoutLocation === null) {
            return response()->json([
                'message' => 'Data lokasi absensi tidak ditemukan.',
            ], 422);
        }

        $distance = Geo::distanceMeters(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            (float) $checkoutLocation->latitude,
            (float) $checkoutLocation->longitude,
        );

        if ($employee->is_attendance_strict && $distance > $checkoutLocation->radius_meters + 25) {
            return response()->json([
                'message' => 'Lokasi GPS di luar radius yang diizinkan.',
            ], 422);
        }

        [$clockOutLat, $clockOutLon] = $this->resolvedStoredCoordinates(
            $employee->is_attendance_strict,
            $checkoutLocation,
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
        );

        $attendance->clock_out_at = $recordedAt;
        $attendance->clock_out_latitude = $clockOutLat;
        $attendance->clock_out_longitude = $clockOutLon;
        $attendance->clock_out_attendance_location_id = $checkoutLocation->id !== $attendance->attendance_location_id
            ? $checkoutLocation->id
            : null;
        $attendance->status = AttendanceDayStatus::Present;
        if (is_string($clientRequestId) && $clientRequestId !== '') {
            $attendance->client_clock_out_request_id = $clientRequestId;
        }
        $attendance->save();

        $this->storeOptimizedAttendancePhoto(
            $attendance,
            $request->file('photo'),
            Attendance::MEDIA_CLOCK_OUT,
        );

        $attendance->load(['attendanceLocation', 'clockOutAttendanceLocation', 'media']);

        return response()->json([
            'message' => 'Absen pulang berhasil.',
            'attendance' => $this->attendancePayload($attendance),
        ]);
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function resolvedStoredCoordinates(
        bool $strict,
        AttendanceLocation $referenceLocation,
        float $clientLatitude,
        float $clientLongitude,
    ): array {
        if ($strict) {
            return [$clientLatitude, $clientLongitude];
        }

        $radius = max(0.0, (float) $referenceLocation->radius_meters);
        if ($radius <= 0.0) {
            return [(float) $referenceLocation->latitude, (float) $referenceLocation->longitude];
        }

        return Geo::randomPointWithinDiskMeters(
            (float) $referenceLocation->latitude,
            (float) $referenceLocation->longitude,
            $radius,
        );
    }

    /**
     * Waktu yang dicatat perangkat (sinkronisasi offline). Tanpa field ini dipakai waktu server.
     *
     * @throws ValidationException
     */
    private function parseClientRecordedAt(?string $raw): CarbonInterface
    {
        if ($raw === null || $raw === '') {
            return now();
        }

        try {
            $t = Carbon::parse($raw);
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'client_recorded_at' => ['Format waktu tidak valid.'],
            ]);
        }

        if ($t->greaterThan(now()->addMinutes(5))) {
            throw ValidationException::withMessages([
                'client_recorded_at' => ['Waktu tidak boleh lebih dari 5 menit di depan waktu server.'],
            ]);
        }

        if ($t->lessThan(now()->subHours(48))) {
            throw ValidationException::withMessages([
                'client_recorded_at' => ['Waktu perekaman terlalu lama (lebih dari 48 jam). Hubungi admin untuk pencatatan manual.'],
            ]);
        }

        // Kolom datetime DB tidak menyimpan offset; simpan jam dinding sesuai zona aplikasi
        // (mis. WITA) agar cocok dengan Filament/laporan. Klien boleh mengirim UTC (…Z).
        return $t->clone()->timezone(config('app.timezone'));
    }

    /**
     * @return array<string, mixed>
     */
    private function attendancePayload(Attendance $attendance): array
    {
        $attendance->loadMissing(['attendanceLocation', 'clockOutAttendanceLocation', 'media']);

        $location = $attendance->attendanceLocation;
        $clockOutLocation = $attendance->clockOutAttendanceLocation;

        $clockInUrl = $attendance->getFirstMediaUrl(Attendance::MEDIA_CLOCK_IN);
        $clockOutUrl = $attendance->getFirstMediaUrl(Attendance::MEDIA_CLOCK_OUT);

        return [
            'id' => $attendance->id,
            'work_date' => $attendance->work_date->format('Y-m-d'),
            'clock_in_at' => $attendance->clock_in_at?->toIso8601String(),
            'clock_out_at' => $attendance->clock_out_at?->toIso8601String(),
            'clock_in_latitude' => $attendance->clock_in_latitude,
            'clock_in_longitude' => $attendance->clock_in_longitude,
            'clock_out_latitude' => $attendance->clock_out_latitude,
            'clock_out_longitude' => $attendance->clock_out_longitude,
            'status' => $attendance->status->value,
            'status_label' => $attendance->status->label(),
            'clock_in_photo_url' => $clockInUrl !== '' ? $clockInUrl : null,
            'clock_out_photo_url' => $clockOutUrl !== '' ? $clockOutUrl : null,
            'location' => $location ? [
                'id' => $location->id,
                'name' => $location->name,
                'address' => $location->address,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'radius_meters' => $location->radius_meters,
            ] : null,
            'clock_out_location' => $clockOutLocation ? [
                'id' => $clockOutLocation->id,
                'name' => $clockOutLocation->name,
                'address' => $clockOutLocation->address,
                'latitude' => $clockOutLocation->latitude,
                'longitude' => $clockOutLocation->longitude,
                'radius_meters' => $clockOutLocation->radius_meters,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mobileLocationPayload(AttendanceLocation $location, bool $fromCompanyDefault): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'address' => $location->address,
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'radius_meters' => $location->radius_meters,
            'from_company_default' => $fromCompanyDefault,
        ];
    }

    private function storeOptimizedAttendancePhoto(
        Attendance $attendance,
        UploadedFile $photo,
        string $collection,
    ): void {
        try {
            $tempPath = AttendancePhotoOptimizer::optimizeToTempFile($photo);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'photo' => [$e->getMessage()],
            ]);
        }

        try {
            $attendance->clearMediaCollection($collection);
            $attendance->addMedia($tempPath)
                ->usingFileName(Str::uuid()->toString().'.jpg')
                ->toMediaCollection($collection);
        } finally {
            if (is_file($tempPath)) {
                unlink($tempPath);
            }
        }
    }
}
