<?php

namespace App\Support;

class Haversine
{
    const R = 6371000; // jari-jari rata-rata bumi dalam meter

    // Menghitung jarak dua titik koordinat (meter)
    public static function jarak(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        // Persamaan (2.1)
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        // Persamaan (2.2)
        return 2 * self::R * atan2(sqrt($a), sqrt(1 - $a));
    }
}