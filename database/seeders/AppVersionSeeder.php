<?php

namespace Database\Seeders;

use App\Enums\AppPlatform;
use App\Models\AppVersion;
use Illuminate\Database\Seeder;

class AppVersionSeeder extends Seeder
{
    public function run(): void
    {
        AppVersion::query()->updateOrCreate(
            [
                'platform' => AppPlatform::Android->value,
                'version_code' => 2,
            ],
            [
                'version_name' => '1.1.0',
                'min_supported_version_code' => 1,
                'download_url' => url('/apk/absen.apk'),
                'release_notes' => "- Deteksi mock GPS untuk pegawai strict.\n- Sesi otomatis kedaluwarsa setelah 30 hari.\n- Pesan rate-limit lebih ramah.\n- Antrian absen offline > 48 jam dibersihkan otomatis.",
                'is_active' => true,
                'released_at' => now(),
            ],
        );
    }
}
