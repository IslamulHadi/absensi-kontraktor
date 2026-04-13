<?php

namespace App\Filament\Resources\Attendances;

use App\Filament\Resources\Attendances\Pages\CreateAttendance;
use App\Filament\Resources\Attendances\Pages\EditAttendance;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\Attendances\Pages\ViewAttendance;
use App\Filament\Resources\Attendances\Schemas\AttendanceForm;
use App\Filament\Resources\Attendances\Schemas\AttendanceInfolist;
use App\Filament\Resources\Attendances\Tables\AttendancesTable;
use App\Models\Attendance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?string $navigationLabel = 'Laporan absensi';

    protected static ?string $modelLabel = 'catatan absensi';

    protected static ?string $pluralModelLabel = 'laporan absensi';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return AttendanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceInfolist::configure($schema);
    }

    /**
     * @return Builder<Attendance>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['employee', 'attendanceLocation', 'media']);
    }

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
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
            'index' => ListAttendances::route('/'),
            'create' => CreateAttendance::route('/create'),
            'view' => ViewAttendance::route('/{record}'),
            'edit' => EditAttendance::route('/{record}/edit'),
        ];
    }
}
