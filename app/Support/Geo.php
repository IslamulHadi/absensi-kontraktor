<?php

namespace App\Support;

final class Geo
{
    /**
     * Great-circle distance between two WGS84 points in meters.
     */
    public static function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusMeters = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusMeters * $c;
    }

    /**
     * Random WGS84 point uniformly distributed inside a flat disk of radius $radiusMeters
     * around ($centerLat, $centerLon). Suitable for small radii (geofencing).
     *
     * @return array{0: float, 1: float} latitude, longitude
     */
    public static function randomPointWithinDiskMeters(float $centerLat, float $centerLon, float $radiusMeters): array
    {
        $u = random_int(1, 1_000_000) / 1_000_000;
        $v = random_int(1, 1_000_000) / 1_000_000;
        $r = $radiusMeters * sqrt($u);
        $theta = 2 * M_PI * $v;
        $dEast = $r * cos($theta);
        $dNorth = $r * sin($theta);
        $latMetersPerDegree = 111_320.0;
        $lonMetersPerDegree = 111_320.0 * cos(deg2rad($centerLat));
        if (abs($lonMetersPerDegree) < 1e-6) {
            $lonMetersPerDegree = 111_320.0;
        }
        $dLat = $dNorth / $latMetersPerDegree;
        $dLon = $dEast / $lonMetersPerDegree;

        return [$centerLat + $dLat, $centerLon + $dLon];
    }
}
