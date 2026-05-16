<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Concerns\SyncsAttendanceFormPhotos;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    use SyncsAttendanceFormPhotos;

    protected static string $resource = AttendanceResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
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
    }
}
