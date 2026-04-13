<?php

namespace App\Filament\Resources\AttendanceLocations\Schemas;

use App\Filament\Forms\Components\AttendanceLocationMapPicker;
use EduardoRibeiroDev\FilamentLeaflet\Enums\TileLayer;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceLocationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama lokasi')
                    ->required()
                    ->maxLength(255),
                TextInput::make('address')
                    ->label('Alamat')
                    ->maxLength(255),
                Section::make('Peta & koordinat')
                    ->description('Tentukan titik lokasi di peta (OpenStreetMap) atau sesuaikan angka lintang/bujur.')
                    ->schema([
                        AttendanceLocationMapPicker::make('location')
                            ->label('Peta lokasi')
                            ->helperText('Lingkaran biru menunjukkan radius izin absensi. Klik peta atau seret penanda untuk mengisi lintang dan bujur; ubah radius di bawah untuk memperbarui zona di peta.')
                            ->height(352)
                            ->center(AttendanceLocationMapPicker::DEFAULT_LAT, AttendanceLocationMapPicker::DEFAULT_LNG)
                            ->zoom(16)
                            ->tileLayersUrl(TileLayer::OpenStreetMap)
                            ->attributionControl(true)
                            ->latitudeFieldName('latitude')
                            ->longitudeFieldName('longitude')
                            ->columnSpanFull(),
                        TextInput::make('latitude')
                            ->label('Garis lintang')
                            ->required()
                            ->numeric()
                            ->step(0.00000001)
                            ->default(AttendanceLocationMapPicker::DEFAULT_LAT)
                            ->live(debounce: 400),
                        TextInput::make('longitude')
                            ->label('Garis bujur')
                            ->required()
                            ->numeric()
                            ->step(0.00000001)
                            ->default(AttendanceLocationMapPicker::DEFAULT_LNG)
                            ->live(debounce: 400),
                        TextInput::make('radius_meters')
                            ->label('Radius izin (meter)')
                            ->required()
                            ->numeric()
                            ->default((int) AttendanceLocationMapPicker::DEFAULT_RADIUS_METERS)
                            ->helperText('Jarak maksimal dari titik lokasi agar absensi dianggap valid (untuk integrasi aplikasi mobile). Lingkaran di peta memperbarui setelah Anda mengubah nilai ini.')
                            ->live(debounce: 400)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
                Toggle::make('is_default')
                    ->label('Lokasi default perusahaan')
                    ->helperText('Jika pegawai belum memiliki lokasi khusus, aplikasi mobile memakai lokasi ini. Hanya satu lokasi yang boleh dijadikan default.')
                    ->default(false),
            ]);
    }
}
