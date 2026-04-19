<?php

namespace App\Filament\Resources\Attendances\Schemas;

use App\Enums\AttendanceDayStatus;
use App\Models\Attendance;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Foto absensi')
                    ->description('Diunggah dari aplikasi mobile')
                    ->schema([
                        ImageEntry::make('clock_in_photo')
                            ->label('Foto masuk')
                            ->state(fn(Attendance $record): ?string => self::photoPathRelativeToDisk($record, Attendance::MEDIA_CLOCK_IN))
                            ->disk(fn(Attendance $record): string => self::photoDiskName($record, Attendance::MEDIA_CLOCK_IN))
                            ->checkFileExistence(false)
                            ->imageHeight(220)
                            ->placeholder('Belum ada foto'),
                        ImageEntry::make('clock_out_photo')
                            ->label('Foto pulang')
                            ->state(fn(Attendance $record): ?string => self::photoPathRelativeToDisk($record, Attendance::MEDIA_CLOCK_OUT))
                            ->disk(fn(Attendance $record): string => self::photoDiskName($record, Attendance::MEDIA_CLOCK_OUT))
                            ->checkFileExistence(false)
                            ->imageHeight(220)
                            ->placeholder('Belum ada foto'),
                    ])
                    ->columns(2),
                Section::make('Ringkasan')
                    ->schema([
                        TextEntry::make('employee.full_name')
                            ->label('Pegawai'),
                        TextEntry::make('employee.nik')
                            ->label('NIK'),
                        TextEntry::make('work_date')
                            ->label('Tanggal kerja')
                            ->date(),
                        TextEntry::make('status')
                            ->label('Status')
                            ->formatStateUsing(function (AttendanceDayStatus|string $state): string {
                                $enum = $state instanceof AttendanceDayStatus
                                    ? $state
                                    : AttendanceDayStatus::from((string) $state);

                                return $enum->label();
                            }),
                        TextEntry::make('clock_in_at')
                            ->label('Jam masuk')
                            ->dateTime(),
                        TextEntry::make('clock_out_at')
                            ->label('Jam pulang')
                            ->dateTime(),
                        TextEntry::make('attendanceLocation.name')
                            ->label('Lokasi masuk')
                            ->placeholder('—'),
                        TextEntry::make('clock_out_location_summary')
                            ->label('Lokasi pulang')
                            ->state(function (Attendance $record): string {
                                if ($record->clock_out_attendance_location_id !== null) {
                                    return $record->clockOutAttendanceLocation?->name ?? '—';
                                }

                                if ($record->attendanceLocation !== null) {
                                    return 'Sama dengan lokasi masuk';
                                }

                                return '—';
                            }),

                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Path relatif ke disk penyimpanan media (bukan URL penuh).
     *
     * Filament ImageEntry memanggil Storage::url() jika state gagal FILTER_VALIDATE_URL.
     * Hostname Herd seperti absensi_kontraktor.test mengandung underscore sehingga URL penuh
     * ditolak PHP dan menghasilkan URL ganda (/storage/http://...).
     */
    private static function photoPathRelativeToDisk(Attendance $record, string $collection): ?string
    {
        $media = $record->getFirstMedia($collection);

        return $media?->getPathRelativeToRoot();
    }

    private static function photoDiskName(Attendance $record, string $collection): string
    {
        return $record->getFirstMedia($collection)?->disk
            ?? config('media-library.disk_name', 'public');
    }
}
