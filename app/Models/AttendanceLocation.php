<?php

namespace App\Models;

use Database\Factories\AttendanceLocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttendanceLocation extends Model
{
    /** @use HasFactory<AttendanceLocationFactory> */
    use HasFactory;

    /**
     * @return list<string>
     */
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'is_active',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'radius_meters' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AttendanceLocation $location): void {
            if (! $location->is_default) {
                return;
            }

            static::query()
                ->when($location->exists, fn ($q) => $q->whereKeyNot($location->id))
                ->update(['is_default' => false]);
        });
    }

    /**
     * @return BelongsToMany<Employee, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class)
            ->withTimestamps();
    }
}
