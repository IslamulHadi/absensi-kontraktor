<?php

use App\Filament\Resources\AttendanceLocations\Pages\CreateAttendanceLocation;
use App\Models\AttendanceLocation;
use App\Models\User;
use Livewire\Livewire;

test('attendance location create form includes map picker from filament-leaflet', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $html = $this->get(route('filament.admin.resources.attendance-locations.create'))
        ->assertOk()
        ->assertSee('OpenStreetMap', false)
        ->getContent();

    expect($html)->toContain('leafletMapField')
        ->and($html)->toMatch('/filament-leaflet|leaflet-map/')
        ->and($html)->toContain('Zona absensi')
        ->and($html)->toContain('flatLatitudePath')
        ->and($html)->toContain('data.latitude')
        ->and($html)->toContain('data.longitude');
});

test('admin can create attendance location without map picker save callback errors', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(CreateAttendanceLocation::class)
        ->fillForm([
            'name' => 'Lokasi uji',
            'latitude' => -8.5,
            'longitude' => 116.1,
            'radius_meters' => 120,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(AttendanceLocation::query()->where('name', 'Lokasi uji')->exists())->toBeTrue();
});
