<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Mengoptimalkan foto absensi ke JPEG dengan ukuran berkas tidak melebihi batas penyimpanan.
 *
 * Target operasional ~500–700 KiB (plafon 700 KiB).
 */
final class AttendancePhotoOptimizer
{
    public const int MAX_BYTES = 716_800;

    private const int MIN_JPEG_QUALITY = 38;

    private const int MAX_EDGE_PX = 1920;

    /**
     * Menulis JPEG teroptimasi ke berkas sementara dan mengembalikan path absolut.
     *
     * @throws RuntimeException jika GD tidak tersedia atau gambar tidak dapat diproses
     */
    public static function optimizeToTempFile(UploadedFile $file): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            throw new RuntimeException('Ekstensi GD diperlukan untuk memproses foto absensi.');
        }

        $realPath = $file->getRealPath();
        if ($realPath === false) {
            throw new RuntimeException('Berkas unggahan tidak dapat dibaca.');
        }

        $binary = file_get_contents($realPath);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('Berkas unggahan kosong.');
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            throw new RuntimeException('Format gambar tidak didukung atau berkas rusak.');
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);
            $maxDim = max($width, $height);

            if ($maxDim > self::MAX_EDGE_PX) {
                $ratio = self::MAX_EDGE_PX / $maxDim;
                $newWidth = max(1, (int) round($width * $ratio));
                $newHeight = max(1, (int) round($height * $ratio));
                $scaled = imagescale($image, $newWidth, $newHeight, IMG_BILINEAR_FIXED);
                imagedestroy($image);
                if ($scaled === false) {
                    throw new RuntimeException('Gagal menyesuaikan ukuran gambar.');
                }
                $image = $scaled;
            }

            $jpegBinary = self::jpegBinaryUnderMaxBytes($image);

            $tempPath = tempnam(sys_get_temp_dir(), 'att_photo_');
            if ($tempPath === false) {
                throw new RuntimeException('Gagal membuat berkas sementara.');
            }

            $jpgPath = $tempPath.'.jpg';
            if (! @rename($tempPath, $jpgPath)) {
                @unlink($tempPath);
                throw new RuntimeException('Gagal menyiapkan berkas JPEG.');
            }

            if (file_put_contents($jpgPath, $jpegBinary) === false) {
                @unlink($jpgPath);
                throw new RuntimeException('Gagal menulis JPEG yang dioptimalkan.');
            }

            return $jpgPath;
        } finally {
            imagedestroy($image);
        }
    }

    /**
     * Menghasilkan string biner JPEG dengan ukuran ≤ {@see self::MAX_BYTES}.
     */
    private static function jpegBinaryUnderMaxBytes(GdImage $source): string
    {
        $w = imagesx($source);
        $h = imagesy($source);
        $working = imagecreatetruecolor($w, $h);
        if ($working === false) {
            return self::jpegEncode($source, self::MIN_JPEG_QUALITY);
        }

        imagecopy($working, $source, 0, 0, 0, 0, $w, $h);

        try {
            for ($round = 0; $round < 18; $round++) {
                $quality = 84;
                while ($quality >= self::MIN_JPEG_QUALITY) {
                    $blob = self::jpegEncode($working, $quality);
                    if (strlen($blob) <= self::MAX_BYTES) {
                        return $blob;
                    }
                    $quality -= 6;
                }

                $cw = imagesx($working);
                $ch = imagesy($working);
                if ($cw <= 520 && $ch <= 520) {
                    return self::jpegEncode($working, self::MIN_JPEG_QUALITY);
                }

                $newWidth = max(480, (int) round($cw * 0.88));
                $scaled = imagescale($working, $newWidth, -1, IMG_BILINEAR_FIXED);
                if ($scaled === false) {
                    return self::jpegEncode($working, self::MIN_JPEG_QUALITY);
                }

                imagedestroy($working);
                $working = $scaled;
            }

            return self::jpegEncode($working, self::MIN_JPEG_QUALITY);
        } finally {
            imagedestroy($working);
        }
    }

    private static function jpegEncode(GdImage $image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, max(self::MIN_JPEG_QUALITY, min(100, $quality)));

        $blob = ob_get_clean();

        return is_string($blob) ? $blob : '';
    }
}
