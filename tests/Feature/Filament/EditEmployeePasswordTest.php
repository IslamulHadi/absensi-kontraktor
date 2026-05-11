<?php

use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('changing employee mobile password persists bcrypt hash not plaintext', function () {
    $admin = User::factory()->admin()->create();
    $employeeUser = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
    $employee = Employee::factory()->create([
        'user_id' => $employeeUser->id,
        'full_name' => 'Pegawai Uji',
    ]);

    $newPlain = 'NewSecure1!Pass';

    Livewire::actingAs($admin)
        ->test(EditEmployee::class, ['record' => $employee->id])
        ->fillForm([
            'new_account_password' => $newPlain,
            'new_account_password_confirmation' => $newPlain,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $employeeUser->refresh();

    expect($employeeUser->password)->not->toBe($newPlain)
        ->and(Hash::check($newPlain, $employeeUser->password))->toBeTrue();
});
