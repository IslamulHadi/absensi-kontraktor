<?php

namespace App\Models;

use App\Enums\AppPlatform;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    /**
     * @return list<string>
     */
    protected $fillable = [
        'platform',
        'version_name',
        'version_code',
        'min_supported_version_code',
        'download_url',
        'release_notes',
        'is_active',
        'released_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'platform' => AppPlatform::class,
            'version_code' => 'integer',
            'min_supported_version_code' => 'integer',
            'is_active' => 'boolean',
            'released_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (AppVersion $version): void {
            if (! $version->is_active) {
                return;
            }

            static::query()
                ->where('platform', $version->platform->value)
                ->when($version->exists, fn ($q) => $q->whereKeyNot($version->id))
                ->update(['is_active' => false]);
        });
    }
}
