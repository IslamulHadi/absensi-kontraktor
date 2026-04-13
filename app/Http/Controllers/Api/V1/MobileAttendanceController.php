<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AttendanceDayStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ClockInAttendanceRequest;
use App\Http\Requests\Api\V1\ClockOutAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Support\Geo;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileAttendanceController extends Controller
{
    public function attendanceLocations(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        [$location, $fromCompanyDefault] = $employee->resolveMobileAttendanceLocationPair();

        if ($location === null) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => [
                $this->mobileLocationPayload($location, $fromCompanyDefault),
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $paginator = Attendance::query()
            ->where('employee_id', $employee->id)
            ->with(['attendanceLocation', 'media'])
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
            ->with(['attendanceLocation', 'media'])
            ->first();

        return response()->json([
            'attendance' => $attendance ? $this->attendancePayload($attendance) : null,
        ]);
    }

    public function clockIn(ClockInAttendanceRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $recordedAt = $this->parseClientRecordedAt($request->validated('client_recorded_at'));

        $clientRequestId = $request->validated('client_request_id');
        if (is_string($clientRequestId) && $clientRequestId !== '') {
            $dup = Attendance::query()
                ->where('employee_id', $employee->id)
                ->where('client_clock_in_request_id', $clientRequestId)
                ->with(['attendanceLocation', 'media'])
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

        if ($distance > $location->radius_meters + 25) {
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
        $attendance->clock_in_latitude = $request->validated('latitude');
        $attendance->clock_in_longitude = $request->validated('longitude');
        $attendance->status = AttendanceDayStatus::Incomplete;
        if (is_string($clientRequestId) && $clientRequestId !== '') {
            $attendance->client_clock_in_request_id = $clientRequestId;
        }
        $attendance->save();

        $attendance->clearMediaCollection(Attendance::MEDIA_CLOCK_IN);
        $attendance->addMediaFromRequest('photo')->toMediaCollection(Attendance::MEDIA_CLOCK_IN);

        $attendance->load(['attendanceLocation', 'media']);

        return response()->json([
            'message' => 'Absen masuk berhasil.',
            'attendance' => $this->attendancePayload($attendance),
        ]);
    }

    public function clockOut(ClockOutAttendanceRequest $request): JsonResponse
    {
        $employee = $request->user()->employee;

        $recordedAt = $this->parseClientRecordedAt($request->validated('client_recorded_at'));

        $clientRequestId = $request->validated('client_request_id');
        if (is_string($clientRequestId) && $clientRequestId !== '') {
            $dup = Attendance::query()
                ->where('employee_id', $employee->id)
                ->where('client_clock_out_request_id', $clientRequestId)
                ->with(['attendanceLocation', 'media'])
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
            ->with('attendanceLocation');

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

        $location = $attendance->attendanceLocation;
        if ($location === null) {
            return response()->json([
                'message' => 'Data lokasi absensi tidak ditemukan.',
            ], 422);
        }

        $distance = Geo::distanceMeters(
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            (float) $location->latitude,
            (float) $location->longitude,
        );

        if ($distance > $location->radius_meters + 25) {
            return response()->json([
                'message' => 'Lokasi GPS di luar radius yang diizinkan.',
            ], 422);
        }

        $attendance->clock_out_at = $recordedAt;
        $attendance->clock_out_latitude = $request->validated('latitude');
        $attendance->clock_out_longitude = $request->validated('longitude');
        $attendance->status = AttendanceDayStatus::Present;
        if (is_string($clientRequestId) && $clientRequestId !== '') {
            $attendance->client_clock_out_request_id = $clientRequestId;
        }
        $attendance->save();

        $attendance->clearMediaCollection(Attendance::MEDIA_CLOCK_OUT);
        $attendance->addMediaFromRequest('photo')->toMediaCollection(Attendance::MEDIA_CLOCK_OUT);

        $attendance->load(['attendanceLocation', 'media']);

        return response()->json([
            'message' => 'Absen pulang berhasil.',
            'attendance' => $this->attendancePayload($attendance),
        ]);
    }

    /**
     * Waktu yang dicatat perangkat (sinkronisasi offline). Tanpa field ini dipakai waktu server.
     *
     * @throws ValidationException
     */
    private function parseClientRecordedAt(?string $raw): Carbon
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

        if ($t->lessThan(now()->subDays(14))) {
            throw ValidationException::withMessages([
                'client_recorded_at' => ['Waktu perekaman terlalu lama (lebih dari 14 hari).'],
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
        $attendance->loadMissing(['attendanceLocation', 'media']);

        $location = $attendance->attendanceLocation;

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
}
