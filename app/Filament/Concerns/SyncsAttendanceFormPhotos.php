<?php

namespace App\Filament\Concerns;

use App\Models\Attendance;
use App\Support\AttendancePhotoOptimizer;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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

            if ($value === null) {
                continue;
            }

            $file = is_array($value) ? ($value[0] ?? null) : $value;

            if ($file instanceof TemporaryUploadedFile) {
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
}
