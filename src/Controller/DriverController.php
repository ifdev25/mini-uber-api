<?php

// src/Controller/DriverController.php

/**
 * Controller for driver-specific endpoints
 * Complements API Platform's automatic CRUD operations with custom business logic
 */

namespace App\Controller;

use App\Entity\Driver;
use App\Service\GeoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/drivers')]
class DriverController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GeoService $geoService
    ) {}

    #[Route('/available', methods: ['GET'])]
    public function getAvailableDrivers(Request $request): JsonResponse
    {
        $lat = $request->query->get('lat');
        $lng = $request->query->get('lng');
        $radius = $request->query->get('radius', 5); // 5km par défaut

        // Requête simple (dans un vrai projet, utiliser une requête géospatiale)
        $drivers = $this->em->getRepository(Driver::class)
            ->createQueryBuilder('d')
            ->where('d.isAvailable = true')
            ->andWhere('d.isVerified = true')
            ->getQuery()
            ->getResult();

        $nearbyDrivers = [];
        foreach ($drivers as $driver) {
            if ($driver->getCurrentLatitude() && $driver->getCurrentLongitude()) {
                $distance = $this->geoService->calculateDistance(
                    $lat,
                    $lng,
                    $driver->getCurrentLatitude(),
                    $driver->getCurrentLongitude()
                );

                if ($distance <= $radius) {
                    $nearbyDrivers[] = [
                        'id' => $driver->getId(),
                        'name' => $driver->getUser()->getFullName(),
                        'rating' => $driver->getUser()->getRating(),
                        'vehicle' => [
                            'model' => $driver->getVehicleModel(),
                            'color' => $driver->getVehicleColor(),
                            'type' => $driver->getVehicleType()
                        ],
                        'location' => [
                            'lat' => $driver->getCurrentLatitude(),
                            'lng' => $driver->getCurrentLongitude()
                        ],
                        'distance' => round($distance, 2)
                    ];
                }
            }
        }

        return new JsonResponse($nearbyDrivers);
    }

    /**
     * Get driver statistics
     */
    #[Route('/stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        if ($user->getUserType() !== 'driver') {
            return new JsonResponse(['error' => 'Not a driver'], 403);
        }

        $driver = $user->getDriver();

        if (!$driver) {
            return new JsonResponse(['error' => 'Driver profile not found'], 404);
        }

        // Get rides statistics for this driver
        $completedRides = $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('App\Entity\Ride', 'r')
            ->where('r.driver = :driver')
            ->andWhere('r.status = :status')
            ->setParameter('driver', $driver)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        $canceledRides = $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('App\Entity\Ride', 'r')
            ->where('r.driver = :driver')
            ->andWhere('r.status = :status')
            ->setParameter('driver', $driver)
            ->setParameter('status', 'canceled')
            ->getQuery()
            ->getSingleScalarResult();

        $totalEarnings = $this->em->createQueryBuilder()
            ->select('SUM(r.finalPrice)')
            ->from('App\Entity\Ride', 'r')
            ->where('r.driver = :driver')
            ->andWhere('r.status = :status')
            ->setParameter('driver', $driver)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        return new JsonResponse([
            'driver' => [
                'id' => $driver->getId(),
                'isAvailable' => $driver->isAvailable(),
                'isVerified' => $driver->isVerified(),
                'vehicleModel' => $driver->getVehicleModel(),
                'vehicleType' => $driver->getVehicleType(),
                'vehicleColor' => $driver->getVehicleColor(),
            ],
            'stats' => [
                'completedRides' => (int) $completedRides,
                'canceledRides' => (int) $canceledRides,
                'totalEarnings' => round((float) ($totalEarnings ?? 0), 2),
                'averageRating' => $user->getRating(),
                'totalRides' => $user->getTotalRides(),
            ]
        ]);
    }
}
