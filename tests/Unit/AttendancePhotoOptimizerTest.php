<?php

use App\Support\AttendancePhotoOptimizer;
use Illuminate\Http\UploadedFile;

test('optimizer reduces large raster to jpeg under max bytes', function () {
    $path = sys_get_temp_dir().'/opt_test_'.uniqid('', true).'.jpg';
    $img = imagecreatetruecolor(3200, 3200);
    $fill = imagecolorallocate($img, 200, 100, 50);
    imagefilledrectangle($img, 0, 0, 3200, 3200, $fill);
    imagejpeg($img, $path, 100);
    imagedestroy($img);

    expect(filesize($path))->toBeGreaterThan(AttendancePhotoOptimizer::MAX_BYTES);

    $uploaded = new UploadedFile($path, 'big.jpg', 'image/jpeg', null, true);

    $out = AttendancePhotoOptimizer::optimizeToTempFile($uploaded);

    expect(file_exists($out))->toBeTrue()
        ->and(filesize($out))->toBeLessThanOrEqual(AttendancePhotoOptimizer::MAX_BYTES);

    @unlink($out);
    @unlink($path);
});
