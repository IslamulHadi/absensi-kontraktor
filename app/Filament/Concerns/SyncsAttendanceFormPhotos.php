<?php

namespace App\Filament\Concerns;

use App\Models\Attendance;
use App\Support\AttendancePhotoOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait SyncsAttendanceFormPhotos
{
    protected function syncAttendanceFormPhotos(Attendance $attendance): void
    {
        $raw = $this->form->getRawState();

        foreach ([
            'clock_in_photo' => Attendance::MEDIA_CLOCK_IN,
            'clock_out_photo' => Attendance::MEDIA_CLOCK_OUT,
        ] as $field => $collection) {
            $value = $raw[$field] ?? null;

            if ($value === [] || $value === '') {
                $attendance->clearMediaCollection($collection);

                continue;
            }

            $file = $this->resolveAttendanceFormPhotoUpload($field, $value);

            if ($file === null) {
                continue;
            }

            if ($file instanceof UploadedFile) {
                try {
                    $tempPath = AttendancePhotoOptimizer::optimizeToTempFile($file);
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages([
                        $field => [$e->getMessage()],
                    ]);
                }

                try {
                    $attendance->clearMediaCollection($collection);
                    $attendance->addMedia($tempPath)
                        ->usingFileName(Str::uuid()->toString().'.jpg')
                        ->toMediaCollection($collection);
                } finally {
                    if (is_file($tempPath)) {
                        unlink($tempPath);
                    }
                }

                continue;
            }

            if (is_string($file) && filled($file)) {
                continue;
            }
        }
    }

    /**
     * @return UploadedFile|string|null
     */
    protected function resolveAttendanceFormPhotoUpload(string $field, mixed $value): UploadedFile|string|null
    {
        $component = $this->form->getComponentByStatePath($field);

        if ($component !== null) {
            $value = $component->getRawState();
        }

        if ($value instanceof UploadedFile) {
            return $value;
        }

        if (is_string($value) && filled($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return null;
        }

        $file = Arr::first($value);

        if ($file instanceof UploadedFile) {
            return $file;
        }

        if (is_string($file) && filled($file)) {
            return $file;
        }

        return null;
    }
}
