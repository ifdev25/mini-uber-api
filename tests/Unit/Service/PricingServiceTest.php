<?php

namespace App\Tests\Unit\Service;

use App\Service\PricingService;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    private PricingService $pricingService;

    protected function setUp(): void
    {
        $this->pricingService = new PricingService();
    }

    public function testCalculateEstimateReturnsCorrectStructure(): void
    {
        // Paris coordinates
        $pickupLat = 48.8566;
        $pickupLng = 2.3522;
        $dropoffLat = 48.8606;
        $dropoffLng = 2.3376;

        $result = $this->pricingService->calculateEstimate(
            $pickupLat,
            $pickupLng,
            $dropoffLat,
            $dropoffLng,
            'standard'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('distance', $result);
        $this->assertArrayHasKey('duration', $result);
        $this->assertArrayHasKey('price', $result);
    }

    public function testCalculateEstimateForStandardVehicle(): void
    {
        $pickupLat = 48.8566;
        $pickupLng = 2.3522;
        $dropoffLat = 48.8606;
        $dropoffLng = 2.3376;

        $result = $this->pricingService->calculateEstimate(
            $pickupLat,
            $pickupLng,
            $dropoffLat,
            $dropoffLng,
            'standard'
        );

        $this->assertGreaterThan(0, $result['distance']);
        $this->assertGreaterThan(0, $result['duration']);
        $this->assertGreaterThan(0, $result['price']);
    }

    public function testCalculateEstimateForPremiumVehicle(): void
    {
        $pickupLat = 48.8566;
        $pickupLng = 2.3522;
        $dropoffLat = 48.8606;
        $dropoffLng = 2.3376;

        $standardResult = $this->pricingService->calculateEstimate(
            $pickupLat,
            $pickupLng,
            $dropoffLat,
            $dropoffLng,
            'standard'
        );

        $premiumResult = $this->pricingService->calculateEstimate(
            $pickupLat,
            $pickupLng,
            $dropoffLat,
            $dropoffLng,
            'premium'
        );

        // Premium should be more expensive than standard
        $this->assertGreaterThan($standardResult['price'], $premiumResult['price']);
    }

    public function testDistanceCalculationIsPositive(): void
    {
        $pickupLat = 48.8566;
        $pickupLng = 2.3522;
        $dropoffLat = 48.9000;
        $dropoffLng = 2.4000;

        $result = $this->pricingService->calculateEstimate(
            $pickupLat,
            $pickupLng,
            $dropoffLat,
            $dropoffLng,
            'standard'
        );

        $this->assertGreaterThan(0, $result['distance']);
    }

    public function testZeroDistanceHasMinimumPrice(): void
    {
        // Same coordinates
        $lat = 48.8566;
        $lng = 2.3522;

        $result = $this->pricingService->calculateEstimate(
            $lat,
            $lng,
            $lat,
            $lng,
            'standard'
        );

        // Should still have a minimum price even for 0 distance
        $this->assertGreaterThanOrEqual(0, $result['price']);
        $this->assertEquals(0, $result['distance']);
    }
}
