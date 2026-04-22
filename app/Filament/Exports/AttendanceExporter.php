<?php

namespace App\Filament\Exports;

use App\Models\Attendance;
use Carbon\CarbonInterface;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class AttendanceExporter extends Exporter
{
    protected static ?string $model = Attendance::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('employee.full_name')
                ->label('Nama'),
            ExportColumn::make('work_date')
                ->label('Tanggal')
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
                ->label('Foto masuk')
                ->state(fn (Attendance $record): string => self::spreadsheetImageCellValue(
                    self::photoFullUrl($record, Attendance::MEDIA_CLOCK_IN),
                )),
            ExportColumn::make('clock_in_location_display')
                ->label('Lokasi absen masuk')
                ->state(fn (Attendance $record): string => self::clockInLocationLabel($record)),
            ExportColumn::make('clock_out_at')
                ->label('Jam keluar')
                ->formatStateUsing(fn (mixed $state): string => self::formatDateTime($state)),
            ExportColumn::make('clock_out_photo_url')
                ->label('Foto keluar')
                ->state(fn (Attendance $record): string => self::spreadsheetImageCellValue(
                    self::photoFullUrl($record, Attendance::MEDIA_CLOCK_OUT),
                )),
            ExportColumn::make('clock_out_location_display')
                ->label('Lokasi absen keluar')
                ->state(fn (Attendance $record): string => self::clockOutLocationLabel($record)),
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

    /**
     * @param  Builder<Attendance>  $query
     * @return Builder<Attendance>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'employee',
            'attendanceLocation',
            'clockOutAttendanceLocation',
            'media',
        ]);
    }

    public function getJobConnection(): ?string
    {
        return 'sync';
    }

    /**
     * @return list<ExportFormat>
     */
    public function getFormats(): array
    {
        return [ExportFormat::Xlsx];
    }

    /**
     * Menyisipkan gambar di Excel (Microsoft 365 / Excel untuk web) lewat fungsi IMAGE.
     * OpenSpout mengenali string yang diawali "=" sebagai formula pada berkas XLSX.
     */
    private static function spreadsheetImageCellValue(string $absoluteUrl): string
    {
        if ($absoluteUrl === '') {
            return '';
        }

        $escaped = str_replace('"', '""', $absoluteUrl);

        return '=IMAGE("'.$escaped.'")';
    }

    private static function clockInLocationLabel(Attendance $record): string
    {
        $name = $record->attendanceLocation?->name;

        if (filled($name)) {
            return $name;
        }

        return self::formatGps($record->clock_in_latitude, $record->clock_in_longitude);
    }

    private static function clockOutLocationLabel(Attendance $record): string
    {
        if ($record->clock_out_attendance_location_id !== null) {
            return filled($record->clockOutAttendanceLocation?->name)
                ? (string) $record->clockOutAttendanceLocation->name
                : '—';
        }

        if ($record->attendance_location_id !== null) {
            return '';
        }

        return self::formatGps($record->clock_out_latitude, $record->clock_out_longitude) ?: '—';
    }

    private static function formatGps(mixed $latitude, mixed $longitude): string
    {
        if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
            return '';
        }

        return trim((string) $latitude).', '.trim((string) $longitude);
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
