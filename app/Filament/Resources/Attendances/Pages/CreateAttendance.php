<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Concerns\SyncsAttendanceFormPhotos;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    use SyncsAttendanceFormPhotos;

    protected static string $resource = AttendanceResource::class;

    protected function afterCreate(): void
    {
        /** @var Attendance $record */
        $record = $this->getRecord();
        $this->syncAttendanceFormPhotos($record);
    }
}
