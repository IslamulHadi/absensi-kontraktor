<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\User;
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
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                IconColumn::make('is_attendance_strict')
                    ->label('Mode strict')
                    ->boolean()
                    ->tooltip('Strict: wajib di radius. Unstrict: bisa submit di luar radius.')
                    ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isSuperAdmin()),
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
                EditAction::make()
                    ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isSuperAdmin()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isSuperAdmin()),
                ]),
            ]);
    }
}
