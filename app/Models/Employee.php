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
        'department',
        'position',
        'is_active',
        'hired_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hired_at' => 'date',
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
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Satu lokasi untuk aplikasi mobile: penugasan yang ditandai utama (pivot is_primary),
     * atau penugasan pertama jika belum ada yang utama, atau lokasi default perusahaan.
     *
     * @return array{0: AttendanceLocation|null, 1: bool} Lokasi, lalu true jika memakai fallback default.
     */
    public function resolveMobileAttendanceLocationPair(): array
    {
        $assigned = $this->attendanceLocations()
            ->where('attendance_locations.is_active', true)
            ->wherePivot('is_primary', true)
            ->orderBy('attendance_locations.name')
            ->first();

        if ($assigned === null) {
            $assigned = $this->attendanceLocations()
                ->where('attendance_locations.is_active', true)
                ->orderBy('attendance_locations.name')
                ->first();
        }

        if ($assigned !== null) {
            return [$assigned, false];
        }

        $default = AttendanceLocation::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('id')
            ->first();

        return [$default, $default !== null];
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
