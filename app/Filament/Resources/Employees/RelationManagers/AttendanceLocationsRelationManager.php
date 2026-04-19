<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttendanceLocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'attendanceLocations';

    protected static ?string $title = 'Lokasi absensi diizinkan';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama lokasi')
                    ->searchable(),
                TextColumn::make('address')
                    ->label('Alamat')
                    ->limit(40),
                TextColumn::make('radius_meters')
                    ->label('Radius (m)')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->label('Aktif (master)')
                    ->boolean()
                    ->tooltip('Status lokasi di data master.'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->modalHeading('Tambah lokasi absensi')
                    ->modalDescription('Pegawai dapat absen di salah satu lokasi yang dilampirkan (tergantur mode strict di data pegawai).')
                    ->preloadRecordSelect()
                    ->attachAnother()
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
