<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Concerns\SyncsAttendanceFormPhotos;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendance extends EditRecord
{
    use SyncsAttendanceFormPhotos;

    protected static string $resource = AttendanceResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        /** @var Attendance $record */
        $record = $this->getRecord();

        $clockIn = $record->getFirstMedia(Attendance::MEDIA_CLOCK_IN);
        $data['clock_in_photo'] = $clockIn?->getPathRelativeToRoot();

        $clockOut = $record->getFirstMedia(Attendance::MEDIA_CLOCK_OUT);
        $data['clock_out_photo'] = $clockOut?->getPathRelativeToRoot();

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Attendance $record */
        $record = $this->getRecord();
        $this->syncAttendanceFormPhotos($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
