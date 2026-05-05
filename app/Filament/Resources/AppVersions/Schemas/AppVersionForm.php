<?php

namespace App\Filament\Resources\AppVersions\Schemas;

use App\Enums\AppPlatform;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AppVersionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('platform')
                    ->label('Platform')
                    ->options(AppPlatform::class)
                    ->default(AppPlatform::Android->value)
                    ->required(),
                TextInput::make('version_name')
                    ->label('Nama Versi')
                    ->placeholder('1.1.0')
                    ->helperText('Format SemVer: MAJOR.MINOR.PATCH')
                    ->required(),
                TextInput::make('version_code')
                    ->label('Build Number')
                    ->helperText('Bilangan bulat naik di setiap rilis. Klien dengan build < ini akan ditawari pembaruan.')
                    ->required()
                    ->numeric(),
                TextInput::make('min_supported_version_code')
                    ->label('Build Minimum yang Didukung')
                    ->helperText('Klien dengan build < ini WAJIB update (force update).')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('download_url')
                    ->label('URL Unduh')
                    ->placeholder('https://absensi-kontraktor.test/apk/absen.apk')
                    ->url(),
                Textarea::make('release_notes')
                    ->label('Catatan Rilis')
                    ->rows(4)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Hanya satu versi yang aktif per platform; mengaktifkan versi ini otomatis menonaktifkan yang lain.')
                    ->required(),
                DateTimePicker::make('released_at')
                    ->label('Tanggal Rilis')
                    ->seconds(false),
            ]);
    }
}
