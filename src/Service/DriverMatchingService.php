<?php

// src/Service/DriverMatchingService.php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\Driver;
use Doctrine\ORM\EntityManagerInterface;

class DriverMatchingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationService $notificationService
    ) {}

    public function notifyNearbyDrivers(Ride $ride): void
    {
        // Trouver les chauffeurs disponibles à proximité
        $drivers = $this->em->getRepository(Driver::class)
            ->createQueryBuilder('d')
            ->where('d.isAvailable = true')
            ->andWhere('d.isVerified = true')
            ->andWhere('d.vehicleType = :vehicleType')
            ->setParameter('vehicleType', $ride->getVehicleType())
            ->getQuery()
            ->getResult();

        $nearbyDriverUsers = [];
        foreach ($drivers as $driver) {
            if ($driver->getCurrentLatitude() && $driver->getCurrentLongitude()) {
                $distance = $this->calculateDistance(
                    $ride->getPickUpLatitude(),
                    $ride->getPickUpLongitude(),
                    $driver->getCurrentLatitude(),
                    $driver->getCurrentLongitude()
                );

                // Si le chauffeur est à moins de 10km
                if ($distance <= 10) {
                    $nearbyDriverUsers[] = $driver->getUser();
                }
            }
        }

        // Envoyer les notifications en temps réel via Mercure
        if (!empty($nearbyDriverUsers)) {
            $this->notificationService->notifyDriversAboutNewRide($ride, $nearbyDriverUsers);
        }
    }

    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}