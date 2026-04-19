<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:64', 'regex:/^[a-zA-Z0-9._-]+$/'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $normalized = Str::lower($validated['username']);

        /** @var User|null $user */
        $user = User::query()->whereRaw('lower(username) = ?', [$normalized])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => [__('auth.failed')],
            ]);
        }

        if ($user->role !== UserRole::Employee) {
            throw ValidationException::withMessages([
                'username' => ['Akun ini tidak dapat digunakan untuk aplikasi pegawai.'],
            ]);
        }

        $employee = $user->employee;

        if (! $employee) {
            throw ValidationException::withMessages([
                'username' => ['Data pegawai untuk akun ini belum ditautkan.'],
            ]);
        }

        if (! $employee->is_active) {
            throw ValidationException::withMessages([
                'username' => ['Akun pegawai tidak aktif.'],
            ]);
        }

        $deviceName = $validated['device_name'] ?? 'mobile';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'employee' => [
                'id' => $employee->id,
                'nik' => $employee->nik,
                'full_name' => $employee->full_name,
                'phone' => $employee->phone,
                'is_attendance_strict' => $employee->is_attendance_strict,
                'attendance_mode' => $employee->is_attendance_strict ? 'strict' : 'unstrict',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user?->load('employee');

        return response()->json([
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ] : null,
            'employee' => $user?->employee ? [
                'id' => $user->employee->id,
                'nik' => $user->employee->nik,
                'full_name' => $user->employee->full_name,
                'phone' => $user->employee->phone,
                'is_attendance_strict' => $user->employee->is_attendance_strict,
                'attendance_mode' => $user->employee->is_attendance_strict ? 'strict' : 'unstrict',
            ] : null,
        ]);
    }
}
