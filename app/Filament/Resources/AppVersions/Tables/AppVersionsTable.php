<?php

namespace App\Filament\Resources\AppVersions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppVersionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('released_at', 'desc')
            ->columns([
                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->searchable(),
                TextColumn::make('version_name')
                    ->label('Versi')
                    ->searchable(),
                TextColumn::make('version_code')
                    ->label('Build')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('min_supported_version_code')
                    ->label('Min. Build')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('released_at')
                    ->label('Dirilis')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('download_url')
                    ->label('URL Unduh')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
