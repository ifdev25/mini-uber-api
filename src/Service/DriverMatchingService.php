<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\Driver;
use Doctrine\ORM\EntityManagerInterface;

class DriverMatchingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationService $notificationService,
        private GeoService $geoService
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
                $distance = $this->geoService->calculateDistance(
                    $ride->getPickupLatitude(),
                    $ride->getPickupLongitude(),
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
}