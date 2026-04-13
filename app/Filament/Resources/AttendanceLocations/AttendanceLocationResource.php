<?php

namespace App\Filament\Resources\AttendanceLocations;

use App\Filament\Resources\AttendanceLocations\Pages\CreateAttendanceLocation;
use App\Filament\Resources\AttendanceLocations\Pages\EditAttendanceLocation;
use App\Filament\Resources\AttendanceLocations\Pages\ListAttendanceLocations;
use App\Filament\Resources\AttendanceLocations\Schemas\AttendanceLocationForm;
use App\Filament\Resources\AttendanceLocations\Tables\AttendanceLocationsTable;
use App\Models\AttendanceLocation;
use BackedEnum;
use Filament\Resources\Resource;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AttendanceLocationResource extends Resource
{
    protected static ?string $model = AttendanceLocation::class;

    protected static string|UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?string $modelLabel = 'lokasi absensi';

    protected static ?string $pluralModelLabel = 'lokasi absensi';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AttendanceLocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendanceLocationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendanceLocations::route('/'),
            'create' => CreateAttendanceLocation::route('/create'),
            'edit' => EditAttendanceLocation::route('/{record}/edit'),
        ];
    }
}
