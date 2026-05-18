<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Concerns\SyncsAttendanceFormPhotos;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateAttendance extends CreateRecord
{
    use SyncsAttendanceFormPhotos;

    protected static string $resource = AttendanceResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function beforeCreate(): void
    {
        $data = $this->form->getRawState();

        if (isset($data['clock_in_at'], $data['clock_out_at'])) {
            $clockIn = Carbon::parse($data['clock_in_at']);
            $clockOut = Carbon::parse($data['clock_out_at']);
            if ($clockOut <= $clockIn) {
                $this->addError('clock_out_at', 'Jam keluar harus lebih besar dari jam masuk.');
                $this->halt();
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $setting = AttendanceSetting::current();

        if (blank($data['clock_in_on_time_at'] ?? null)) {
            $data['clock_in_on_time_at'] = $setting->clock_in_on_time_at;
        }

        if (($data['clock_in_tolerance_minutes'] ?? null) === null || $data['clock_in_tolerance_minutes'] === '') {
            $data['clock_in_tolerance_minutes'] = $setting->clock_in_tolerance_minutes;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Attendance $record */
        $record = $this->getRecord();
        $this->syncAttendanceFormPhotos($record);

        // Isi UUID biar keliatan dari mobile app
        if ($record->client_clock_in_request_id === null) {
            $record->client_clock_in_request_id = (string) Str::uuid();
        }
        if ($record->client_clock_out_request_id === null && $record->clock_out_at !== null) {
            $record->client_clock_out_request_id = (string) Str::uuid();
        }
        $record->save();
    }
}
