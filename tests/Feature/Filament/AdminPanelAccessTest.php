<?php

use App\Enums\UserRole;
use App\Models\User;

test('non admin users cannot access the filament admin panel', function () {
    $user = User::factory()->create([
        'role' => UserRole::Employee,
    ]);

    $this->actingAs($user);

    $this->get('/admin')->assertForbidden();
});

test('admin users can open the filament admin panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->get('/admin')->assertOk();
});
