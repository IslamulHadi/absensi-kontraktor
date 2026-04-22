<?php

use Illuminate\Support\Facades\File;

test('apk download returns 404 when file is missing', function (): void {
    $path = public_path('apk/absen.apk');
    $backup = null;
    if (is_file($path)) {
        $backup = sys_get_temp_dir().'/absen-apk-test-'.uniqid('', true).'.bak';
        rename($path, $backup);
    }

    try {
        $this->get(route('downloads.absen-apk'))->assertNotFound();
    } finally {
        if ($backup !== null && is_file($backup)) {
            rename($backup, $path);
        }
    }
});

test('apk download succeeds when file exists', function (): void {
    $path = public_path('apk/absen.apk');
    File::ensureDirectoryExists(dirname($path));

    $createdStub = false;
    if (! is_file($path)) {
        file_put_contents($path, 'fake-apk');
        $createdStub = true;
    }

    try {
        $this->get(route('downloads.absen-apk'))
            ->assertSuccessful()
            ->assertHeaderContains('content-disposition', 'absen.apk');
    } finally {
        if ($createdStub && is_file($path)) {
            unlink($path);
        }
    }
});
