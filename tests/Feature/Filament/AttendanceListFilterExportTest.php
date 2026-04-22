<?php

use App\Filament\Exports\AttendanceExporter;
use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Models\Attendance;
use App\Models\User;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use ReflectionMethod;
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

test('attendance exporter exposes fixed report columns', function () {
    $names = array_map(
        static fn (ExportColumn $column): string => $column->getName(),
        AttendanceExporter::getColumns()
    );

    expect($names)->toBe([
        'employee.full_name',
        'work_date',
        'clock_in_at',
        'clock_in_photo_url',
        'clock_in_location_display',
        'clock_out_at',
        'clock_out_photo_url',
        'clock_out_location_display',
    ]);
});

test('attendance exporter runs export jobs on the sync connection', function () {
    $user = User::factory()->admin()->create();

    $export = Export::create([
        'file_disk' => 'local',
        'file_name' => 'test',
        'exporter' => AttendanceExporter::class,
        'total_rows' => 0,
        'user_id' => $user->id,
    ]);

    $exporter = $export->getExporter(
        columnMap: ['work_date' => 'Tanggal'],
        options: [],
    );

    expect($exporter->getJobConnection())->toBe('sync');
});

test('attendance exporter only offers xlsx so photo cells use excel formulas', function () {
    $user = User::factory()->admin()->create();

    $export = Export::create([
        'file_disk' => 'local',
        'file_name' => 'test',
        'exporter' => AttendanceExporter::class,
        'total_rows' => 0,
        'user_id' => $user->id,
    ]);

    $exporter = $export->getExporter(columnMap: ['work_date' => 'Tanggal'], options: []);

    expect($exporter->getFormats())->toBe([ExportFormat::Xlsx]);
});

test('spreadsheet image cell value wraps url in excel image formula', function () {
    $method = new ReflectionMethod(AttendanceExporter::class, 'spreadsheetImageCellValue');
    $method->setAccessible(true);

    expect($method->invoke(null, ''))->toBe('')
        ->and($method->invoke(null, 'https://example.com/a.jpg'))->toBe('=IMAGE("https://example.com/a.jpg")')
        ->and($method->invoke(null, 'https://example.com/a"b.jpg'))->toBe('=IMAGE("https://example.com/a""b.jpg")');
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
