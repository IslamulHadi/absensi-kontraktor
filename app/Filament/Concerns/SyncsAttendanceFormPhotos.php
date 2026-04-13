<?php

namespace App\Filament\Concerns;

use App\Models\Attendance;
use Illuminate\Support\Str;
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
                $attendance->clearMediaCollection($collection);
                $attendance->addMedia($file->getRealPath())
                    ->usingFileName($file->getClientOriginalName())
                    ->usingName(Str::beforeLast($file->getClientOriginalName(), '.'))
                    ->toMediaCollection($collection);

                continue;
            }

            if (is_string($file) && filled($file)) {
                continue;
            }
        }
    }
}
