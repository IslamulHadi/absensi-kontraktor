<?php

namespace App\Models;

use App\Enums\AttendanceDayStatus;
use Database\Factories\AttendanceFactory;
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

    /**
     * @return list<string>
     */
    protected $fillable = [
        'employee_id',
        'attendance_location_id',
        'clock_out_attendance_location_id',
        'work_date',
        'clock_in_at',
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
            'clock_in_latitude' => 'decimal:8',
            'clock_in_longitude' => 'decimal:8',
            'clock_out_latitude' => 'decimal:8',
            'clock_out_longitude' => 'decimal:8',
            'status' => AttendanceDayStatus::class,
        ];
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
