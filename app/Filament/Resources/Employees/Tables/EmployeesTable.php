<?php

namespace App\Filament\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.username')
                    ->label('Username (mobile)')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('department')
                    ->label('Departemen')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('position')
                    ->label('Jabatan')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('hired_at')
                    ->label('Mulai kerja')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
