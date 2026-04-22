<?php

namespace App\Filament\Resources\Attendances\Tables;

use App\Enums\AttendanceDayStatus;
use App\Filament\Exports\AttendanceExporter;
use App\Models\Attendance;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('work_date', direction: 'desc')
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.nik')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('work_date')
                    ->label('Tanggal kerja')
                    ->date()
                    ->sortable(),
                TextColumn::make('clock_in_at')
                    ->label('Masuk')
                    ->dateTime()
                    ->sortable(),
                ImageColumn::make('clock_in_photo')
                    ->label('Foto masuk')
                    ->state(fn (Attendance $record): ?string => self::photoPathRelativeToDisk($record, Attendance::MEDIA_CLOCK_IN))
                    ->disk(fn (Attendance $record): string => self::photoDiskName($record, Attendance::MEDIA_CLOCK_IN))
                    ->checkFileExistence(false)
                    ->imageHeight(48)
                    ->toggleable(),
                TextColumn::make('clock_out_at')
                    ->label('Keluar')
                    ->dateTime()
                    ->sortable(),
                ImageColumn::make('clock_out_photo')
                    ->label('Foto pulang')
                    ->state(fn (Attendance $record): ?string => self::photoPathRelativeToDisk($record, Attendance::MEDIA_CLOCK_OUT))
                    ->disk(fn (Attendance $record): string => self::photoDiskName($record, Attendance::MEDIA_CLOCK_OUT))
                    ->checkFileExistence(false)
                    ->imageHeight(48)
                    ->toggleable(),
                TextColumn::make('attendanceLocation.name')
                    ->label('Lokasi')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function (AttendanceDayStatus|string $state): string {
                        $enum = $state instanceof AttendanceDayStatus
                            ? $state
                            : AttendanceDayStatus::from((string) $state);

                        return $enum->label();
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Dicatat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('work_date')
                    ->label('Tanggal kerja')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Dari tanggal')
                            ->native(false),
                        DatePicker::make('until')
                            ->label('Sampai tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                filled($data['from'] ?? null),
                                fn (Builder $q): Builder => $q->whereDate('work_date', '>=', $data['from']),
                            )
                            ->when(
                                filled($data['until'] ?? null),
                                fn (Builder $q): Builder => $q->whereDate('work_date', '<=', $data['until']),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['from'] ?? null)) {
                            $indicators[] = Indicator::make('Tanggal kerja dari '.Carbon::parse($data['from'])->format('d/m/Y'))
                                ->removeField('from');
                        }

                        if (filled($data['until'] ?? null)) {
                            $indicators[] = Indicator::make('Tanggal kerja sampai '.Carbon::parse($data['until'])->format('d/m/Y'))
                                ->removeField('until');
                        }

                        return $indicators;
                    }),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(AttendanceDayStatus::cases())->mapWithKeys(fn (AttendanceDayStatus $s) => [$s->value => $s->label()])),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Ekspor laporan')
                    ->exporter(AttendanceExporter::class)
                    ->columnMapping(false),
            ])
            ->recordActions([
                ViewAction::make(),
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

    /** Path relatif ke disk media; sama seperti infolist (hindari URL ganda bila host ber-underscore). */
    private static function photoPathRelativeToDisk(Attendance $record, string $collection): ?string
    {
        $media = $record->getFirstMedia($collection);

        return $media?->getPathRelativeToRoot();
    }

    private static function photoDiskName(Attendance $record, string $collection): string
    {
        return $record->getFirstMedia($collection)?->disk
            ?? config('media-library.disk_name', 'public');
    }
}
