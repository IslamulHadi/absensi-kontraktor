<?php

use App\Filament\Exports\AttendanceExporter;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Models\Attendance;
use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('attendance list filters rows by work date range', function () {
    $admin = User::factory()->admin()->create();

    $insideRange = Attendance::factory()->create([
        'work_date' => '2026-04-15',
    ]);

    $outsideRange = Attendance::factory()->create([
        'work_date' => '2026-05-10',
    ]);

    Livewire::actingAs($admin)
        ->test(ListAttendances::class)
        ->assertTableHeaderActionsExistInOrder(['export'])
        ->filterTable('work_date', [
            'from' => '2026-04-01',
            'until' => '2026-04-30',
        ])
        ->assertCountTableRecords(1)
        ->assertCanSeeTableRecords([$insideRange])
        ->assertCanNotSeeTableRecords([$outsideRange]);
});

test('attendance exporter includes photo link columns', function () {
    $names = array_map(
        static fn (ExportColumn $column): string => $column->getName(),
        AttendanceExporter::getColumns()
    );

    expect($names)->toContain('clock_in_photo_url', 'clock_out_photo_url');
});

test('attendance clock in photo resolves to absolute url for spreadsheet links', function () {
    Storage::fake('public');

    $attendance = Attendance::factory()->create();
    $attendance->addMedia(UploadedFile::fake()->image('clock-in.jpg'))
        ->toMediaCollection(Attendance::MEDIA_CLOCK_IN);

    $url = $attendance->getFirstMedia(Attendance::MEDIA_CLOCK_IN)?->getFullUrl() ?? '';

    expect($url)->not->toBe('')
        ->and($url)->toStartWith(rtrim((string) config('app.url'), '/'));
});
