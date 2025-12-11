<?php

namespace App\Service;

/**
 * Service pour les calculs géographiques
 */
class GeoService
{
    /**
     * Calcule la distance entre deux points géographiques en utilisant la formule de Haversine
     *
     * @param float $lat1 Latitude du point 1
     * @param float $lng1 Longitude du point 1
     * @param float $lat2 Latitude du point 2
     * @param float $lng2 Longitude du point 2
     * @return float Distance en kilomètres
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en kilomètres

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
