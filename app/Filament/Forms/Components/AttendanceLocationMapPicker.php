<?php

namespace App\Filament\Forms\Components;

use EduardoRibeiroDev\FilamentLeaflet\Fields\MapPicker;
use EduardoRibeiroDev\FilamentLeaflet\Support\Markers\Marker;
use EduardoRibeiroDev\FilamentLeaflet\Support\Shapes\Circle;
use Filament\Schemas\Components\Utilities\Get;

class AttendanceLocationMapPicker extends MapPicker
{
    public const DEFAULT_LAT = -8.581767056232298;

    public const DEFAULT_LNG = 116.08659259624237;

    public const DEFAULT_RADIUS_METERS = 100.0;

    protected function setUp(): void
    {
        parent::setUp();

        // MapPicker's default callback expects dehydrated picker state; our lat/lng live on
        // sibling TextInputs and are saved with the record already — a null $state here caused
        // "Trying to access array offset on null" on save (see vendor MapPicker.php lines 29–30).
        $this->saveRelationshipsUsing(null);

        $this->pickMarker(fn (): Marker => (new Marker(self::DEFAULT_LAT, self::DEFAULT_LNG))->draggable());

        $this->key(function (Get $get): string {
            $payload = json_encode([
                $get('latitude'),
                $get('longitude'),
                $get('radius_meters'),
            ], JSON_THROW_ON_ERROR);

            return 'attendance-location-map-'.hash('xxh128', $payload);
        });
    }

    /**
     * @return array<int, Circle>
     */
    protected function getShapes(): array
    {
        $livewire = $this->getLivewire();

        /** @var array<string, mixed> $data */
        $data = $livewire->data ?? [];

        $lat = self::floatFromState($data['latitude'] ?? null, self::DEFAULT_LAT);
        $lng = self::floatFromState($data['longitude'] ?? null, self::DEFAULT_LNG);
        $radius = self::floatFromState($data['radius_meters'] ?? null, self::DEFAULT_RADIUS_METERS);

        if ($radius < 1) {
            $radius = 1.0;
        }

        return [
            Circle::make($lat, $lng)
                ->radius($radius)
                ->title('Zona absensi')
                ->blue()
                ->fillBlue()
                ->fillOpacity(0.18),
        ];
    }

    /**
     * Expose sibling TextInput state paths so the Leaflet field script can sync pin ↔ lat/lng
     * (the vendor script otherwise reads/writes only the virtual map field path, e.g. data.location).
     *
     * @return array<string, mixed>
     */
    public function getMapData(): array
    {
        $data = parent::getMapData();

        $root = $this->getRootContainer()?->getStatePath();
        $latName = $this->latitudeFieldName ?? 'latitude';
        $lngName = $this->longitudeFieldName ?? 'longitude';

        if (filled($root)) {
            $data['state']['flatLatitudePath'] = "{$root}.{$latName}";
            $data['state']['flatLongitudePath'] = "{$root}.{$lngName}";
        }

        return $data;
    }

    private static function floatFromState(mixed $value, float $fallback): float
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        if (is_numeric($value)) {
            $n = (float) $value;

            return is_finite($n) ? $n : $fallback;
        }

        $normalized = str_replace(',', '.', (string) $value);
        $n = (float) $normalized;

        return is_finite($n) ? $n : $fallback;
    }
}
