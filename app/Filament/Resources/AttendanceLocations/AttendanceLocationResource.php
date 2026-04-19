<?php

namespace App\Filament\Resources\AttendanceLocations;

use App\Filament\Resources\AttendanceLocations\Pages\CreateAttendanceLocation;
use App\Filament\Resources\AttendanceLocations\Pages\EditAttendanceLocation;
use App\Filament\Resources\AttendanceLocations\Pages\ListAttendanceLocations;
use App\Filament\Resources\AttendanceLocations\Schemas\AttendanceLocationForm;
use App\Filament\Resources\AttendanceLocations\Tables\AttendanceLocationsTable;
use App\Models\AttendanceLocation;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

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

    // public static function canCreate(): bool
    // {
    //     return self::isCurrentUserSuperAdmin();
    // }

    // public static function canEdit(mixed $record): bool
    // {
    //     return self::isCurrentUserSuperAdmin();
    // }

    // public static function canDelete(mixed $record): bool
    // {
    //     return self::isCurrentUserSuperAdmin();
    // }

    // public static function canDeleteAny(): bool
    // {
    //     return self::isCurrentUserSuperAdmin();
    // }

    // private static function isCurrentUserSuperAdmin(): bool
    // {
    //     $user = auth()->user();

    //     return $user instanceof User && $user->isSuperAdmin();
    // }
}
