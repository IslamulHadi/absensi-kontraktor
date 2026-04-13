<?php

namespace App\Filament\Resources\Attendances\Schemas;

use App\Enums\AttendanceDayStatus;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ringkasan')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Pegawai')
                            ->relationship('employee', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (mixed $state, Set $set): void {
                                $employeeId = filled($state) ? (int) $state : null;

                                if ($employeeId === null) {
                                    $set('attendance_location_id', null);
                                    $set('clock_in_latitude', null);
                                    $set('clock_in_longitude', null);
                                    $set('clock_out_latitude', null);
                                    $set('clock_out_longitude', null);

                                    return;
                                }

                                $employee = Employee::query()->find($employeeId);

                                if ($employee === null) {
                                    return;
                                }

                                [$location] = $employee->resolveMobileAttendanceLocationPair();

                                if ($location === null) {
                                    $set('attendance_location_id', null);
                                    $set('clock_in_latitude', null);
                                    $set('clock_in_longitude', null);
                                    $set('clock_out_latitude', null);
                                    $set('clock_out_longitude', null);

                                    return;
                                }

                                $set('attendance_location_id', $location->id);
                                $set('clock_in_latitude', $location->latitude);
                                $set('clock_in_longitude', $location->longitude);
                                $set('clock_out_latitude', $location->latitude);
                                $set('clock_out_longitude', $location->longitude);
                            }),
                        Select::make('attendance_location_id')
                            ->label('Lokasi absensi')
                            ->relationship('attendanceLocation', 'name')
                            ->searchable()
                            ->preload(),
                        DatePicker::make('work_date')
                            ->label('Tanggal kerja')
                            ->required()
                            ->native(false),
                        Select::make('status')
                            ->label('Status')
                            ->options(collect(AttendanceDayStatus::cases())->mapWithKeys(
                                fn (AttendanceDayStatus $s) => [$s->value => $s->label()]
                            ))
                            ->default(AttendanceDayStatus::Incomplete->value)
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Waktu & koordinat')
                    ->schema([
                        DateTimePicker::make('clock_in_at')
                            ->label('Jam masuk')
                            ->seconds(false),
                        DateTimePicker::make('clock_out_at')
                            ->label('Jam keluar')
                            ->seconds(false),
                        TextInput::make('clock_in_latitude')
                            ->label('Lat masuk')
                            ->numeric(),
                        TextInput::make('clock_in_longitude')
                            ->label('Lng masuk')
                            ->numeric(),
                        TextInput::make('clock_out_latitude')
                            ->label('Lat keluar')
                            ->numeric(),
                        TextInput::make('clock_out_longitude')
                            ->label('Lng keluar')
                            ->numeric(),
                    ])
                    ->columns(2)
                    ->collapsed(),
                Section::make('Foto absensi')
                    ->schema([
                        FileUpload::make('clock_in_photo')
                            ->label('Foto masuk')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240)
                            ->maxFiles(1)
                            ->storeFiles(false)
                            ->disk(config('media-library.disk_name', 'public'))
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->dehydrated(false),
                        FileUpload::make('clock_out_photo')
                            ->label('Foto keluar')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(10240)
                            ->maxFiles(1)
                            ->storeFiles(false)
                            ->disk(config('media-library.disk_name', 'public'))
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsed(),
                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ]);
    }
}
