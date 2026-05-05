<?php

namespace App\Filament\Resources\AppVersions;

use App\Filament\Resources\AppVersions\Pages\CreateAppVersion;
use App\Filament\Resources\AppVersions\Pages\EditAppVersion;
use App\Filament\Resources\AppVersions\Pages\ListAppVersions;
use App\Filament\Resources\AppVersions\Schemas\AppVersionForm;
use App\Filament\Resources\AppVersions\Tables\AppVersionsTable;
use App\Models\AppVersion;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Override;

class AppVersionResource extends Resource
{
    protected static ?string $model = AppVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $modelLabel = 'Versi Aplikasi';

    protected static ?string $pluralModelLabel = 'Versi Aplikasi';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return AppVersionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppVersionsTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return self::isCurrentUserSuperAdmin();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    private static function isCurrentUserSuperAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppVersions::route('/'),
            'create' => CreateAppVersion::route('/create'),
            'edit' => EditAppVersion::route('/{record}/edit'),
        ];
    }
}
