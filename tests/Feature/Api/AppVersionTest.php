<?php

use App\Enums\AppPlatform;
use App\Models\AppVersion;

test('returns 404 when no active version exists', function () {
    $response = $this->getJson('/api/v1/app/latest-version?platform=android');

    $response->assertNotFound()
        ->assertJsonPath('message', 'Tidak ada versi aktif untuk platform Android.');
});

test('returns active version for android', function () {
    AppVersion::query()->create([
        'platform' => AppPlatform::Android,
        'version_name' => '1.1.0',
        'version_code' => 2,
        'min_supported_version_code' => 1,
        'download_url' => 'https://example.test/apk/absen.apk',
        'release_notes' => 'Mock GPS detection.',
        'is_active' => true,
        'released_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/app/latest-version?platform=android');

    $response->assertOk()
        ->assertJsonPath('data.platform', 'android')
        ->assertJsonPath('data.version_name', '1.1.0')
        ->assertJsonPath('data.version_code', 2)
        ->assertJsonPath('data.min_supported_version_code', 1)
        ->assertJsonPath('data.download_url', 'https://example.test/apk/absen.apk');
});

test('defaults to android when platform query is missing', function () {
    AppVersion::query()->create([
        'platform' => AppPlatform::Android,
        'version_name' => '1.0.0',
        'version_code' => 1,
        'is_active' => true,
    ]);

    $this->getJson('/api/v1/app/latest-version')
        ->assertOk()
        ->assertJsonPath('data.platform', 'android');
});

test('rejects unsupported platform values', function () {
    $this->getJson('/api/v1/app/latest-version?platform=windows')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['platform']);
});

test('saving an active version deactivates other versions on same platform', function () {
    $oldActive = AppVersion::query()->create([
        'platform' => AppPlatform::Android,
        'version_name' => '1.0.0',
        'version_code' => 1,
        'is_active' => true,
    ]);
    $iosActive = AppVersion::query()->create([
        'platform' => AppPlatform::Ios,
        'version_name' => '1.0.0',
        'version_code' => 1,
        'is_active' => true,
    ]);

    AppVersion::query()->create([
        'platform' => AppPlatform::Android,
        'version_name' => '1.1.0',
        'version_code' => 2,
        'is_active' => true,
    ]);

    expect($oldActive->fresh()->is_active)->toBeFalse()
        ->and($iosActive->fresh()->is_active)->toBeTrue();
});
