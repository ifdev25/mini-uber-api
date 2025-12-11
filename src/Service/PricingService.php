<?php

namespace App\Service;

class PricingService
{
    private const BASE_PRICE = 2.50;
    private const PRICE_PER_KM = 1.20;
    private const PRICE_PER_MINUTE = 0.25;

    private const VEHICLE_TYPE_MULTIPLIERS = [
        'standard' => 1.0,
        'comfort' => 1.5,
        'premium' => 2.0,
        'xl' => 1.8,
    ];

    public function __construct(private GeoService $geoService)
    {
    }

    public function calculateEstimate(
        float $pickupLat,
        float $pickupLng,
        float $dropoffLat,
        float $dropoffLng,
        string $vehicleType = 'standard'
    ): array {
        // Calculate distance using GeoService
        $distance = $this->geoService->calculateDistance($pickupLat, $pickupLng, $dropoffLat, $dropoffLng);

        // Estimate duration (average speed 30 km/h in city)
        $durationMinutes = ($distance / 30) * 60;

        // Calculate base price
        $basePrice = self::BASE_PRICE + ($distance * self::PRICE_PER_KM) + ($durationMinutes * self::PRICE_PER_MINUTE);

        // Apply vehicle type multiplier
        $multiplier = self::VEHICLE_TYPE_MULTIPLIERS[$vehicleType] ?? 1.0;
        $finalPrice = $basePrice * $multiplier;

        return [
            'distance' => round($distance, 2),
            'duration' => round($durationMinutes, 0),
            'price' => round($finalPrice, 2),
            'vehicleType' => $vehicleType,
        ];
    }
}
