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

#[Route('/api')]
class DriverController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private GeoService $geoService
    ) {}

    #[Route('/drivers-available', methods: ['GET'])]
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
     * Get driver ride history
     * Returns all rides for the authenticated driver in a simple, frontend-friendly format
     */
    #[Route('/driver/history', methods: ['GET'])]
    public function getHistory(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        if ($user->getUserType() !== 'driver') {
            return new JsonResponse(['error' => 'Not a driver'], 403);
        }

        // Get query parameters for filtering and pagination
        $status = $request->query->get('status'); // optional: completed, cancelled, etc.
        $limit = $request->query->get('limit', 20); // default 20 rides
        $offset = $request->query->get('offset', 0); // for pagination

        // Build query
        $qb = $this->em->createQueryBuilder()
            ->select('r')
            ->from('App\Entity\Ride', 'r')
            ->where('r.driver = :driver')
            ->setParameter('driver', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        // Add status filter if provided
        if ($status) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $status);
        }

        $rides = $qb->getQuery()->getResult();

        // Format rides for frontend
        $formattedRides = array_map(function($ride) {
            return [
                'id' => $ride->getId(),
                'status' => $ride->getStatus(),
                'passenger' => [
                    'id' => $ride->getPassenger()->getId(),
                    'name' => $ride->getPassenger()->getFullName(),
                    'phone' => $ride->getPassenger()->getPhone(),
                    'rating' => $ride->getPassenger()->getRating(),
                ],
                'pickup' => [
                    'address' => $ride->getPickupAddress(),
                    'latitude' => $ride->getPickupLatitude(),
                    'longitude' => $ride->getPickupLongitude(),
                ],
                'dropoff' => [
                    'address' => $ride->getDropoffAddress(),
                    'latitude' => $ride->getDropoffLatitude(),
                    'longitude' => $ride->getDropoffLongitude(),
                ],
                'price' => [
                    'estimated' => $ride->getEstimatedPrice(),
                    'final' => $ride->getFinalPrice(),
                ],
                'distance' => $ride->getEstimatedDistance(),
                'duration' => $ride->getEstimatedDuration(),
                'vehicleType' => $ride->getVehicleType(),
                'dates' => [
                    'created' => $ride->getCreatedAt()?->format('Y-m-d H:i:s'),
                    'accepted' => $ride->getAcceptedAt()?->format('Y-m-d H:i:s'),
                    'started' => $ride->getStartedAt()?->format('Y-m-d H:i:s'),
                    'completed' => $ride->getCompletedAt()?->format('Y-m-d H:i:s'),
                ],
            ];
        }, $rides);

        return new JsonResponse([
            'success' => true,
            'data' => $formattedRides,
            'pagination' => [
                'limit' => (int) $limit,
                'offset' => (int) $offset,
                'count' => count($formattedRides),
            ]
        ]);
    }

    /**
     * Get driver statistics
     */
    #[Route('/driver/stats', methods: ['GET'])]
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

    /**
     * Toggle driver availability
     * Accepts application/json (frontend-friendly)
     */
    #[Route('/driver/availability', methods: ['PATCH'])]
    public function toggleAvailability(Request $request): JsonResponse
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

        $data = json_decode($request->getContent(), true);

        if (!isset($data['isAvailable']) || !is_bool($data['isAvailable'])) {
            return new JsonResponse(['error' => 'isAvailable field is required and must be a boolean'], 400);
        }

        $driver->setIsAvailable($data['isAvailable']);
        $this->em->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Availability updated successfully',
            'data' => [
                'id' => $driver->getId(),
                'isAvailable' => $driver->isAvailable(),
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName()
                ]
            ]
        ]);
    }

}
