<?php

namespace App\Filament\Resources\Employees;

use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Employees\RelationManagers\AttendanceLocationsRelationManager;
use App\Filament\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Resources\Employees\Tables\EmployeesTable;
use App\Models\Employee;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|UnitEnum|null $navigationGroup = 'Absensi';

    protected static ?string $modelLabel = 'pegawai';

    protected static ?string $pluralModelLabel = 'pegawai';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AttendanceLocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'edit' => EditEmployee::route('/{record}/edit'),
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
