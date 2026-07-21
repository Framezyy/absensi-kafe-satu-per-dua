<?php

namespace App\Services;

class GeofenceService
{
    public static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function isInsideRadius(float $lat, float $lng, float $centerLat, float $centerLng, int $radiusMeter): bool
    {
        return self::haversineDistance($lat, $lng, $centerLat, $centerLng) <= $radiusMeter;
    }
}
