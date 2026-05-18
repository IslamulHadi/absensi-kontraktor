<?php

namespace App\Models;

use App\Enums\AttendanceDayStatus;
use Carbon\CarbonInterface;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Attendance extends Model implements HasMedia
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory, InteractsWithMedia;

    public const string MEDIA_CLOCK_IN = 'clock_in';

    public const string MEDIA_CLOCK_OUT = 'clock_out';

    protected static function booted(): void
    {
        static::saving(function (Attendance $attendance): void {
            // Validasi: clock_out harus > clock_in
            if ($attendance->clock_out_at !== null && $attendance->clock_in_at !== null) {
                if ($attendance->clock_out_at->lessThanOrEqualTo($attendance->clock_in_at)) {
                    $attendance->clock_out_at = null;
                }
            }
        });

        static::saved(function (Attendance $attendance): void {
            $attendance->syncTimestampsFromClockTimes();
        });
    }

    /**
     * Keep created_at / updated_at aligned with check-in / check-out so listings and exports match absensi narrative.
     * When there is no check-out time yet, updated_at matches check-in until pulang is recorded.
     */
    public function syncTimestampsFromClockTimes(): void
    {
        if ($this->clock_in_at === null) {
            return;
        }

        $createdAt = $this->clock_in_at;
        $updatedAt = $this->clock_out_at ?? $this->clock_in_at;

        if (
            $this->created_at !== null
            && $this->updated_at !== null
            && $this->created_at->equalTo($createdAt)
            && $this->updated_at->equalTo($updatedAt)
        ) {
            return;
        }

        static::query()->whereKey($this->getKey())->update([
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);

        $this->refresh();
    }

    /**
     * @return list<string>
     */
    protected $fillable = [
        'employee_id',
        'attendance_location_id',
        'clock_out_attendance_location_id',
        'work_date',
        'clock_in_at',
        'clock_in_on_time_at',
        'clock_in_tolerance_minutes',
        'clock_out_at',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out_latitude',
        'clock_out_longitude',
        'status',
        'notes',
        'client_clock_in_request_id',
        'client_clock_out_request_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'clock_in_tolerance_minutes' => 'integer',
            'clock_in_latitude' => 'decimal:8',
            'clock_in_longitude' => 'decimal:8',
            'clock_out_latitude' => 'decimal:8',
            'clock_out_longitude' => 'decimal:8',
            'status' => AttendanceDayStatus::class,
        ];
    }

    /**
     * Snapshot the current attendance setting (jam masuk standar + toleransi)
     * onto this row so future setting changes never rewrite history. Idempotent:
     * never overwrites an already-snapshotted value.
     */
    public function applyClockInToleranceSnapshot(?AttendanceSetting $setting = null): void
    {
        $setting ??= AttendanceSetting::current();

        if (blank($this->clock_in_on_time_at)) {
            $this->clock_in_on_time_at = $setting->clock_in_on_time_at;
        }

        if ($this->clock_in_tolerance_minutes === null) {
            $this->clock_in_tolerance_minutes = $setting->clock_in_tolerance_minutes;
        }
    }

    /**
     * Selisih menit antara clock_in_at dan batas akhir toleransi yang berlaku
     * pada hari tersebut. Mengembalikan `null` ketika data tidak cukup, atau 0
     * ketika tepat waktu / lebih awal dari batas toleransi.
     */
    protected function lateMinutes(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                $clockInAt = $this->clock_in_at;
                $onTime = $this->clock_in_on_time_at;

                if (! $clockInAt instanceof CarbonInterface || blank($onTime)) {
                    return null;
                }

                $tolerance = max(0, (int) ($this->clock_in_tolerance_minutes ?? 0));

                $deadline = $clockInAt->copy()
                    ->setTimeFromTimeString((string) $onTime)
                    ->addMinutes($tolerance);

                if ($clockInAt->lessThanOrEqualTo($deadline)) {
                    return 0;
                }

                return (int) $deadline->diffInMinutes($clockInAt);
            },
        );
    }

    protected function isLate(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $minutes = $this->late_minutes;

                return $minutes !== null && $minutes > 0;
            },
        );
    }

    public function registerMediaCollections(): void
    {
        $imageMimes = ['image/jpeg', 'image/png', 'image/webp'];

        $this->addMediaCollection(self::MEDIA_CLOCK_IN)
            ->acceptsMimeTypes($imageMimes)
            ->singleFile();

        $this->addMediaCollection(self::MEDIA_CLOCK_OUT)
            ->acceptsMimeTypes($imageMimes)
            ->singleFile();
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<AttendanceLocation, $this>
     */
    public function attendanceLocation(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class);
    }

    /**
     * @return BelongsTo<AttendanceLocation, $this>
     */
    public function clockOutAttendanceLocation(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'clock_out_attendance_location_id');
    }
}
