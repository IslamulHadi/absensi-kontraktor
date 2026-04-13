<?php

namespace App\Filament\Exports;

use App\Enums\AttendanceDayStatus;
use App\Models\Attendance;
use Carbon\CarbonInterface;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class AttendanceExporter extends Exporter
{
    protected static ?string $model = Attendance::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('employee.full_name')
                ->label('Nama pegawai'),
            ExportColumn::make('employee.nik')
                ->label('NIK'),
            ExportColumn::make('work_date')
                ->label('Tanggal kerja')
                ->formatStateUsing(function (mixed $state): string {
                    if ($state instanceof CarbonInterface) {
                        return $state->format('Y-m-d');
                    }

                    return (string) $state;
                }),
            ExportColumn::make('clock_in_at')
                ->label('Jam masuk')
                ->formatStateUsing(fn (mixed $state): string => self::formatDateTime($state)),
            ExportColumn::make('clock_in_photo_url')
                ->label('Tautan foto masuk')
                ->state(fn (Attendance $record): string => self::photoFullUrl($record, Attendance::MEDIA_CLOCK_IN)),
            ExportColumn::make('clock_out_at')
                ->label('Jam keluar')
                ->formatStateUsing(fn (mixed $state): string => self::formatDateTime($state)),
            ExportColumn::make('clock_out_photo_url')
                ->label('Tautan foto keluar')
                ->state(fn (Attendance $record): string => self::photoFullUrl($record, Attendance::MEDIA_CLOCK_OUT)),
            ExportColumn::make('attendanceLocation.name')
                ->label('Lokasi'),
            ExportColumn::make('clock_in_latitude')
                ->label('Lat masuk')
                ->enabledByDefault(false),
            ExportColumn::make('clock_in_longitude')
                ->label('Lon masuk')
                ->enabledByDefault(false),
            ExportColumn::make('clock_out_latitude')
                ->label('Lat keluar')
                ->enabledByDefault(false),
            ExportColumn::make('clock_out_longitude')
                ->label('Lon keluar')
                ->enabledByDefault(false),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(function (mixed $state): string {
                    if ($state instanceof AttendanceDayStatus) {
                        return $state->label();
                    }

                    return AttendanceDayStatus::tryFrom((string) $state)?->label() ?? (string) $state;
                }),
            ExportColumn::make('notes')
                ->label('Catatan'),
            ExportColumn::make('created_at')
                ->label('Dicatat pada')
                ->formatStateUsing(fn (mixed $state): string => self::formatDateTime($state)),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $successful = Number::format($export->successful_rows);
        $body = "Ekspor laporan absensi selesai: {$successful} baris berhasil diekspor.";

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' baris gagal.';
        }

        return $body;
    }

    private static function formatDateTime(mixed $state): string
    {
        if ($state instanceof CarbonInterface) {
            return $state->format('Y-m-d H:i:s');
        }

        return $state !== null && $state !== '' ? (string) $state : '';
    }

    private static function photoFullUrl(Attendance $record, string $collection): string
    {
        $media = $record->getFirstMedia($collection);

        if ($media === null) {
            return '';
        }

        return $media->getFullUrl();
    }
}
