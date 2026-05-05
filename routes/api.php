<?php

use App\Http\Controllers\Api\V1\AppVersionController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MobileAttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('app/latest-version', [AppVersionController::class, 'latest']);

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login');

    Route::middleware(['auth:sanctum', 'throttle:mobile-api'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::prefix('me')->group(function (): void {
            Route::get('attendance-locations', [MobileAttendanceController::class, 'attendanceLocations']);
            Route::get('attendances', [MobileAttendanceController::class, 'index']);
            Route::get('attendances/today', [MobileAttendanceController::class, 'today']);
            Route::post('attendances/clock-in', [MobileAttendanceController::class, 'clockIn']);
            Route::post('attendances/clock-out', [MobileAttendanceController::class, 'clockOut']);
        });
    });
});
