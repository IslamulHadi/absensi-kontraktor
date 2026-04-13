<?php

namespace App\Filament\Widgets;

use App\Enums\AttendanceDayStatus;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Models\Attendance;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentAttendancesTableWidget extends TableWidget
{
    protected static ?int $sort = -4;

    /**
     * @var int | string | array<string, int | null>
     */
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Absensi terbaru')
            ->description('Klik baris untuk membuka detail.')
            ->query(fn (): Builder => Attendance::query()
                ->with(['employee', 'attendanceLocation'])
                ->orderByDesc('work_date')
                ->orderByDesc('clock_in_at')
                ->orderByDesc('id'))
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Pegawai')
                    ->placeholder('—'),
                TextColumn::make('work_date')
                    ->label('Tanggal kerja')
                    ->date(),
                TextColumn::make('clock_in_at')
                    ->label('Masuk')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('clock_out_at')
                    ->label('Keluar')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function (AttendanceDayStatus|string $state): string {
                        $enum = $state instanceof AttendanceDayStatus
                            ? $state
                            : AttendanceDayStatus::from((string) $state);

                        return $enum->label();
                    }),
                TextColumn::make('attendanceLocation.name')
                    ->label('Lokasi')
                    ->placeholder('—'),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label('Lihat semua')
                    ->url(AttendanceResource::getUrl()),
            ])
            ->recordUrl(fn (Attendance $record): string => AttendanceResource::getUrl('view', ['record' => $record]));
    }
}
