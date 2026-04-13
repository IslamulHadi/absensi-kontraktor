<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected ?string $pendingAccountUsername = null;

    protected ?string $pendingAccountPassword = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $raw = $this->form->getRawState();
        $this->pendingAccountUsername = isset($raw['account_username'])
            ? Str::lower(trim((string) $raw['account_username']))
            : null;
        $this->pendingAccountPassword = $raw['account_password'] ?? null;
        unset($data['user_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! filled($this->pendingAccountUsername) || ! filled($this->pendingAccountPassword)) {
            return;
        }

        $user = User::query()->create([
            'name' => $this->record->full_name,
            'username' => $this->pendingAccountUsername,
            'email' => $this->pendingAccountUsername.'@employees.internal',
            'password' => $this->pendingAccountPassword,
            'role' => UserRole::Employee,
        ]);

        $this->record->forceFill(['user_id' => $user->id])->save();
    }
}
