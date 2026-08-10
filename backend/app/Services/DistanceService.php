<?php

namespace App\Services;

class DistanceService
{
    /**
     * Haversine Formula
     *
     * The Haversine formula calculates the great-circle distance between two points
     * on the Earth's surface using their latitude and longitude coordinates.
     *
     * @param float|string|null $lat1 Consumer latitude
     * @param float|string|null $lon1 Consumer longitude
     * @param float|string|null $lat2 Vendor/store latitude
     * @param float|string|null $lon2 Vendor/store longitude
     * @return float|null Distance in meters, or null if any coordinate is invalid.
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2): ?float
    {
        // Validate coordinates: return null if any are missing or non-numeric
        if (!is_numeric($lat1) || !is_numeric($lon1) || !is_numeric($lat2) || !is_numeric($lon2)) {
            return null;
        }

        $lat1 = (float) $lat1;
        $lon1 = (float) $lon1;
        $lat2 = (float) $lat2;
        $lon2 = (float) $lon2;

        // Latitude and longitude are initially expressed in degrees,
        // but PHP's trigonometric functions operate using radians.
        // Therefore, we convert all coordinates from degrees to radians.
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);

        // Calculate differences in latitude (Δlat) and longitude (Δlon) in radians.
        $deltaPhi = deg2rad($lat2 - $lat1);
        $deltaLambda = deg2rad($lon2 - $lon1);

        // R represents the mean radius of the Earth in meters.
        $R = 6371000;

        // a = sin²(Δlat / 2) + cos(lat1) × cos(lat2) × sin²(Δlon / 2)
        // This is the square of half the chord length between the points.
        $a = sin($deltaPhi / 2) * sin($deltaPhi / 2) +
             cos($phi1) * cos($phi2) *
             sin($deltaLambda / 2) * sin($deltaLambda / 2);

        // c = 2 × atan2(√a, √(1-a))
        // This represents the angular distance (central angle) in radians.
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // The final distance is the Earth's radius multiplied by the angular distance.
        // The result is in meters.
        $distance = $R * $c;

        return $distance;
    }
}
