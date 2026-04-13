<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

test('employee with linked profile receives a sanctum token on login', function () {
    $user = User::factory()->create([
        'username' => 'pegawai1',
        'email' => 'pegawai1@employees.internal',
        'password' => 'password',
        'role' => UserRole::Employee,
    ]);

    Employee::factory()->for($user)->create([
        'nik' => '12345',
        'full_name' => 'Pegawai Satu',
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'username' => 'pegawai1',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('employee.nik', '12345')
        ->assertJsonStructure(['token', 'token_type', 'user', 'employee']);

    $this->assertNotEmpty($response->json('token'));
});

test('admin users cannot use mobile employee login', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'username' => $admin->username,
        'password' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['username']);
});

test('authenticated employee can fetch me', function () {
    $user = User::factory()->create([
        'role' => UserRole::Employee,
    ]);

    Employee::factory()->for($user)->create([
        'nik' => '999',
        'is_active' => true,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonPath('employee.nik', '999');
});

test('logout revokes current token', function () {
    $user = User::factory()->create([
        'role' => UserRole::Employee,
    ]);

    Employee::factory()->for($user)->create(['is_active' => true]);

    $token = $user->createToken('test')->plainTextToken;

    $this->assertDatabaseCount('personal_access_tokens', 1);

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});
