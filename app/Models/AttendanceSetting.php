<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row holding company-wide attendance configuration that admins can
 * tweak from Filament (jam masuk standar + toleransi keterlambatan).
 */
class AttendanceSetting extends Model
{
    /**
     * Default jam masuk standar when no row exists yet.
     */
    public const string DEFAULT_CLOCK_IN_ON_TIME_AT = '08:00:00';

    /**
     * Default toleransi (menit) when no row exists yet.
     */
    public const int DEFAULT_CLOCK_IN_TOLERANCE_MINUTES = 15;

    /**
     * @return list<string>
     */
    protected $fillable = [
        'clock_in_on_time_at',
        'clock_in_tolerance_minutes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clock_in_tolerance_minutes' => 'integer',
        ];
    }

    /**
     * Always work against the single row. Created lazily so seeding/migration
     * order never matters when the API or admin reaches for the setting.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(
            [],
            [
                'clock_in_on_time_at' => self::DEFAULT_CLOCK_IN_ON_TIME_AT,
                'clock_in_tolerance_minutes' => self::DEFAULT_CLOCK_IN_TOLERANCE_MINUTES,
            ],
        );
    }

    /**
     * Normalize Filament `H:i` input + sloppy `H:i:s` strings into a single
     * `H:i:s` representation that the database accepts.
     */
    public function setClockInOnTimeAtAttribute(mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['clock_in_on_time_at'] = null;

            return;
        }

        $raw = (string) $value;
        $parts = explode(':', $raw);
        $hour = (int) ($parts[0] ?? 0);
        $minute = (int) ($parts[1] ?? 0);
        $second = (int) ($parts[2] ?? 0);

        $this->attributes['clock_in_on_time_at'] = sprintf('%02d:%02d:%02d', $hour, $minute, $second);
    }
}
