<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Str;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected ?string $pendingAccountUsername = null;

    protected ?string $pendingAccountPassword = null;

    protected ?string $pendingNewPassword = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['display_user_username'] = $this->record->user?->username ?? '';

        return $data;
    }

    protected function beforeSave(): void
    {
        $raw = $this->form->getRawState();
        $this->pendingAccountUsername = isset($raw['account_username'])
            ? Str::lower(trim((string) $raw['account_username']))
            : null;
        $this->pendingAccountPassword = $raw['account_password'] ?? null;
        $this->pendingNewPassword = $raw['new_account_password'] ?? null;
    }

    protected function afterSave(): void
    {
        if ($this->record->user_id === null
            && filled($this->pendingAccountUsername)
            && filled($this->pendingAccountPassword)
        ) {
            $user = User::query()->create([
                'name' => $this->record->full_name,
                'username' => $this->pendingAccountUsername,
                'email' => $this->pendingAccountUsername.'@employees.internal',
                'password' => $this->pendingAccountPassword,
                'role' => UserRole::Employee,
            ]);
            $this->record->forceFill(['user_id' => $user->id])->save();
        }

        $this->record->load('user');

        if ($this->record->user_id && filled($this->pendingNewPassword)) {
            $this->record->user()->update([
                'password' => $this->pendingNewPassword,
            ]);
        }

        $this->record->user?->update([
            'name' => $this->record->full_name,
        ]);

        $this->pendingAccountUsername = null;
        $this->pendingAccountPassword = null;
        $this->pendingNewPassword = null;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
