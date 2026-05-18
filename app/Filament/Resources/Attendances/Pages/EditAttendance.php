<?php

namespace App\Filament\Resources\Attendances\Pages;

use App\Filament\Concerns\SyncsAttendanceFormPhotos;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use Carbon\Carbon;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['clock_in_at'], $data['clock_out_at'])) {
            $clockIn = Carbon::parse($data['clock_in_at']);
            $clockOut = Carbon::parse($data['clock_out_at']);
            if ($clockOut <= $clockIn) {
                throw ValidationException::withMessages([
                    'clock_out_at' => 'Jam keluar harus lebih besar dari jam masuk.',
                ]);
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Attendance $record */
        $record = $this->getRecord();
        $this->syncAttendanceFormPhotos($record);

        // Isi UUID palsu biar keliatan dari mobile app
        if ($record->client_clock_in_request_id === null) {
            $record->client_clock_in_request_id = (string) Str::uuid();
        }
        if ($record->client_clock_out_request_id === null && $record->clock_out_at !== null) {
            $record->client_clock_out_request_id = (string) Str::uuid();
        }
        $record->save();
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
