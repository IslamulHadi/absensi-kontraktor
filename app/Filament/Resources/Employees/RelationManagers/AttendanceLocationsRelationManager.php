<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Models\AttendanceLocation;
use App\Models\Employee;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
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
                IconColumn::make('is_primary')
                    ->label('Aktif untuk absensi')
                    ->getStateUsing(fn (AttendanceLocation $record): bool => (bool) $record->pivot->is_primary)
                    ->boolean()
                    ->tooltip('Lokasi yang dipakai aplikasi mobile untuk absen. Hanya satu yang boleh aktif; gunakan aksi "Jadikan aktif" pada baris lain untuk mengganti.'),
                IconColumn::make('is_active')
                    ->label('Aktif (master)')
                    ->boolean()
                    ->tooltip('Status lokasi di data master (bukan penanda absensi pegawai).'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->modalHeading('Tambah lokasi absensi')
                    ->modalDescription('Anda boleh menambahkan beberapa lokasi. Yang dipakai untuk absensi mobile hanya satu — tandai lewat kolom "Aktif untuk absensi" atau aksi "Jadikan aktif".')
                    ->preloadRecordSelect()
                    ->attachAnother()
                    ->recordSelectOptionsQuery(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->after(function (AttachAction $action): void {
                        $record = $action->getRecord();
                        if (! $record instanceof AttendanceLocation) {
                            return;
                        }

                        $employee = $this->getOwnerRecord();
                        if (! $employee instanceof Employee) {
                            return;
                        }

                        $this->ensurePrimaryAfterAttach($employee, $record->getKey());
                    }),
            ])
            ->recordActions([
                Action::make('activateForAttendance')
                    ->label('Jadikan aktif absensi')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (AttendanceLocation $record): bool => ! (bool) $record->pivot->is_primary)
                    ->requiresConfirmation()
                    ->modalHeading('Jadikan lokasi aktif untuk absensi?')
                    ->modalDescription('Hanya satu lokasi yang aktif untuk absensi mobile. Lokasi lain akan ditandai tidak aktif untuk penugasan ini.')
                    ->action(function (AttendanceLocation $record): void {
                        $employee = $this->getOwnerRecord();
                        if (! $employee instanceof Employee) {
                            return;
                        }

                        $employee->load('attendanceLocations');
                        foreach ($employee->attendanceLocations as $loc) {
                            $employee->attendanceLocations()->updateExistingPivot($loc->id, [
                                'is_primary' => $loc->id === $record->id,
                            ]);
                        }
                    }),
                DetachAction::make()
                    ->after(function (): void {
                        $employee = $this->getOwnerRecord();
                        if (! $employee instanceof Employee) {
                            return;
                        }

                        $this->ensurePrimaryExistsForEmployee($employee);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make()
                        ->after(function (): void {
                            $employee = $this->getOwnerRecord();
                            if (! $employee instanceof Employee) {
                                return;
                            }

                            $this->ensurePrimaryExistsForEmployee($employee);
                        }),
                ]),
            ]);
    }

    protected function ensurePrimaryAfterAttach(Employee $employee, int $newLocationId): void
    {
        $employee->unsetRelation('attendanceLocations');
        $employee->load('attendanceLocations');

        $count = $employee->attendanceLocations->count();

        if ($count === 1) {
            $employee->attendanceLocations()->updateExistingPivot($newLocationId, ['is_primary' => true]);

            return;
        }

        $hasPrimary = $employee->attendanceLocations->contains(
            fn (AttendanceLocation $loc): bool => (bool) $loc->pivot->is_primary,
        );

        if (! $hasPrimary) {
            $employee->attendanceLocations()->updateExistingPivot($newLocationId, ['is_primary' => true]);
        }
    }

    protected function ensurePrimaryExistsForEmployee(Employee $employee): void
    {
        $employee->unsetRelation('attendanceLocations');
        $employee->load('attendanceLocations');

        if ($employee->attendanceLocations->isEmpty()) {
            return;
        }

        $hasPrimary = $employee->attendanceLocations->contains(
            fn (AttendanceLocation $loc): bool => (bool) $loc->pivot->is_primary,
        );

        if ($hasPrimary) {
            return;
        }

        $first = $employee->attendanceLocations->sortBy('name')->first();
        if ($first !== null) {
            $employee->attendanceLocations()->updateExistingPivot($first->id, ['is_primary' => true]);
        }
    }
}
