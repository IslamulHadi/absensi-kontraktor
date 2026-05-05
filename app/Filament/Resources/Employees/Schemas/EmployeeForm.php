<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Employee;
use App\Models\User;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;
use Livewire\Component as LivewireComponent;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data pegawai')
                    ->schema([
                        TextInput::make('nik')
                            ->label('NIK / Nomor pegawai')
                            ->required()
                            ->maxLength(64)
                            ->unique(ignoreRecord: true),
                        TextInput::make('full_name')
                            ->label('Nama lengkap')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(32),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->required(),
                        Toggle::make('is_attendance_strict')
                            ->label('Mode absensi strict')
                            ->helperText('Jika aktif: pegawai wajib berada di lokasi/radius absensi. Jika nonaktif: pegawai tetap memilih lokasi, tetapi boleh submit dari luar radius.')
                            ->default(false)
                            ->required()
                            ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->isSuperAdmin()),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Akun aplikasi mobile')
                    ->description('Username dan password untuk login di aplikasi mobile (opsional saat tambah pegawai; bisa ditambahkan nanti saat mengubah data)')
                    ->schema([
                        TextInput::make('display_user_username')
                            ->label('Username akun saat ini')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(function (LivewireComponent&HasSchemas $livewire, string $operation): bool {
                                if ($operation !== 'edit') {
                                    return false;
                                }
                                $record = $livewire->getRecord();

                                return $record instanceof Employee && $record->user_id !== null;
                            }),
                        TextInput::make('account_username')
                            ->label('Username login')
                            ->helperText('Huruf kecil/besar, angka, titik, garis bawah, atau tanda hubung. Min. 3 karakter.')
                            ->minLength(3)
                            ->maxLength(64)
                            ->regex('/^[a-zA-Z0-9._-]+$/')
                            ->unique(
                                table: 'users',
                                column: 'username',
                                ignorable: static function (Field $component): ?User {
                                    $livewire = $component->getLivewire();
                                    if (! $livewire instanceof HasSchemas) {
                                        return null;
                                    }
                                    $record = $livewire->getRecord();
                                    if (! $record instanceof Employee || $record->user_id === null) {
                                        return null;
                                    }

                                    return User::query()->find($record->user_id);
                                },
                            )
                            ->dehydrated(false)
                            ->visible(function (LivewireComponent&HasSchemas $livewire, string $operation): bool {
                                if ($operation === 'create') {
                                    return true;
                                }
                                if ($operation !== 'edit') {
                                    return false;
                                }
                                $record = $livewire->getRecord();

                                return $record instanceof Employee && $record->user_id === null;
                            }),
                        TextInput::make('account_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->rule(Password::defaults())
                            ->requiredWith('account_username')
                            ->dehydrated(false)
                            ->visible(function (LivewireComponent&HasSchemas $livewire, string $operation): bool {
                                if ($operation === 'create') {
                                    return true;
                                }
                                if ($operation !== 'edit') {
                                    return false;
                                }
                                $record = $livewire->getRecord();

                                return $record instanceof Employee && $record->user_id === null;
                            }),
                        TextInput::make('account_password_confirmation')
                            ->label('Konfirmasi password')
                            ->password()
                            ->revealable()
                            ->requiredWith('account_username')
                            ->same('account_password')
                            ->dehydrated(false)
                            ->visible(function (LivewireComponent&HasSchemas $livewire, string $operation): bool {
                                if ($operation === 'create') {
                                    return true;
                                }
                                if ($operation !== 'edit') {
                                    return false;
                                }
                                $record = $livewire->getRecord();

                                return $record instanceof Employee && $record->user_id === null;
                            }),
                        TextInput::make('new_account_password')
                            ->label('Password baru')
                            ->helperText('Kosongkan jika tidak ingin mengubah password.')
                            ->password()
                            ->revealable()
                            ->rules(['nullable', Password::defaults()])
                            ->dehydrated(false)
                            ->visible(function (LivewireComponent&HasSchemas $livewire, string $operation): bool {
                                if ($operation !== 'edit') {
                                    return false;
                                }
                                $record = $livewire->getRecord();

                                return $record instanceof Employee && $record->user_id !== null;
                            }),
                        TextInput::make('new_account_password_confirmation')
                            ->label('Konfirmasi password baru')
                            ->password()
                            ->revealable()
                            ->requiredWith('new_account_password')
                            ->same('new_account_password')
                            ->dehydrated(false)
                            ->visible(function (LivewireComponent&HasSchemas $livewire, string $operation): bool {
                                if ($operation !== 'edit') {
                                    return false;
                                }
                                $record = $livewire->getRecord();

                                return $record instanceof Employee && $record->user_id !== null;
                            }),
                    ])
                    ->columns(2),
            ]);
    }
}
