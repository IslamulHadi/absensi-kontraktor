<?php

use Illuminate\Support\Facades\Route;

Route::get('/favicon.svg', function () {
    $path = public_path('favicon.svg');

    if (! is_file($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Content-Type' => 'image/svg+xml; charset=utf-8',
    ]);
})->name('favicon.svg');

Route::get('/apk/absen.apk', function () {
    $path = public_path('apk/absen.apk');

    if (! is_file($path)) {
        abort(404);
    }

    return response()->download($path, 'absen.apk', [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
})->name('downloads.absen-apk');

Route::redirect('/', '/admin');
Route::redirect('/login', '/admin');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
