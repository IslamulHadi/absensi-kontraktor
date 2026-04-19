<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read User|null $user
 */
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_attendance_strict' => false,
    ];

    protected static function booted(): void
    {
        static::deleting(function (Employee $employee): void {
            if ($employee->user_id) {
                $employee->user?->delete();
            }
        });
    }

    /**
     * @return list<string>
     */
    protected $fillable = [
        'user_id',
        'nik',
        'full_name',
        'phone',
        'is_active',
        'is_attendance_strict',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_attendance_strict' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<AttendanceLocation, $this>
     */
    public function attendanceLocations(): BelongsToMany
    {
        return $this->belongsToMany(AttendanceLocation::class)
            ->withTimestamps();
    }

    /**
     * @return array{0: list<AttendanceLocation>, 1: bool} Daftar lokasi, lalu true jika memakai fallback default.
     */
    public function resolveMobileAttendanceLocations(): array
    {
        $assigned = $this->attendanceLocations()
            ->where('attendance_locations.is_active', true)
            ->orderBy('attendance_locations.name')
            ->get()
            ->values()
            ->all();

        if ($assigned !== []) {
            return [$assigned, false];
        }

        $default = AttendanceLocation::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('id')
            ->first();

        if ($default === null) {
            return [[], false];
        }

        return [[$default], true];
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
