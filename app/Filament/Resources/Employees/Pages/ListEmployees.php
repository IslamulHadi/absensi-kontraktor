<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Employee;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('setAllStrict')
                ->label('Set semua mode: Strict')
                ->icon('heroicon-o-shield-check')
                ->color('success')
                ->visible(fn (): bool => $this->isCurrentUserSuperAdmin())
                ->requiresConfirmation()
                ->modalHeading('Ubah semua pegawai ke mode Strict?')
                ->modalDescription('Semua pegawai hanya bisa absen jika berada di lokasi/radius yang ditentukan.')
                ->action(function (): void {
                    Employee::query()->update(['is_attendance_strict' => true]);

                    Notification::make()
                        ->title('Semua pegawai sekarang mode Strict.')
                        ->success()
                        ->send();
                }),
            Action::make('setAllUnstrict')
                ->label('Set semua mode: Unstrict')
                ->icon('heroicon-o-globe-alt')
                ->color('warning')
                ->visible(fn (): bool => $this->isCurrentUserSuperAdmin())
                ->requiresConfirmation()
                ->modalHeading('Ubah semua pegawai ke mode Unstrict?')
                ->modalDescription('Pegawai tetap memilih lokasi absen, namun sistem mengizinkan submit meski di luar radius.')
                ->action(function (): void {
                    Employee::query()->update(['is_attendance_strict' => false]);

                    Notification::make()
                        ->title('Semua pegawai sekarang mode Unstrict.')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function isCurrentUserSuperAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }
}
