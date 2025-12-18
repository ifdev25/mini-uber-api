<?php

// src/Controller/PassengerController.php

/**
 * Controller for passenger-specific endpoints
 * Provides statistics and history for passengers
 */

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class PassengerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    /**
     * Get passenger statistics
     * Returns total rides, completed rides, and total spent
     */
    #[Route('/passenger/stats', methods: ['GET'])]
    public function getStats(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        if ($user->getUserType() !== 'passenger') {
            return new JsonResponse(['error' => 'Not a passenger'], 403);
        }

        // Get total rides count
        $totalRides = $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('App\Entity\Ride', 'r')
            ->where('r.passenger = :passenger')
            ->setParameter('passenger', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // Get completed rides count
        $completedRides = $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('App\Entity\Ride', 'r')
            ->where('r.passenger = :passenger')
            ->andWhere('r.status = :status')
            ->setParameter('passenger', $user)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        // Get total spent (sum of final prices for completed rides)
        $totalSpent = $this->em->createQueryBuilder()
            ->select('SUM(r.finalPrice)')
            ->from('App\Entity\Ride', 'r')
            ->where('r.passenger = :passenger')
            ->andWhere('r.status = :status')
            ->setParameter('passenger', $user)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();

        // Get cancelled rides count
        $cancelledRides = $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('App\Entity\Ride', 'r')
            ->where('r.passenger = :passenger')
            ->andWhere('r.status = :status')
            ->setParameter('passenger', $user)
            ->setParameter('status', 'cancelled')
            ->getQuery()
            ->getSingleScalarResult();

        return new JsonResponse([
            'success' => true,
            'passenger' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'rating' => $user->getRating(),
            ],
            'stats' => [
                'totalRides' => (int) $totalRides,
                'completedRides' => (int) $completedRides,
                'cancelledRides' => (int) $cancelledRides,
                'totalSpent' => round((float) ($totalSpent ?? 0), 2),
                'averageRidePrice' => $completedRides > 0
                    ? round((float) ($totalSpent ?? 0) / (int) $completedRides, 2)
                    : 0,
            ]
        ]);
    }

    /**
     * Get passenger ride history
     * Returns all rides for the authenticated passenger in a simple, frontend-friendly format
     */
    #[Route('/passenger/history', methods: ['GET'])]
    public function getHistory(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        if ($user->getUserType() !== 'passenger') {
            return new JsonResponse(['error' => 'Not a passenger'], 403);
        }

        // Get query parameters for filtering and pagination
        $status = $request->query->get('status'); // optional: completed, cancelled, etc.
        $limit = $request->query->get('limit', 20); // default 20 rides
        $offset = $request->query->get('offset', 0); // for pagination

        // Build query
        $qb = $this->em->createQueryBuilder()
            ->select('r')
            ->from('App\Entity\Ride', 'r')
            ->where('r.passenger = :passenger')
            ->setParameter('passenger', $user)
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
            $rideData = [
                'id' => $ride->getId(),
                'status' => $ride->getStatus(),
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

            // Add driver info if assigned
            if ($ride->getDriver()) {
                $rideData['driver'] = [
                    'id' => $ride->getDriver()->getId(),
                    'name' => $ride->getDriver()->getFullName(),
                    'phone' => $ride->getDriver()->getPhone(),
                    'rating' => $ride->getDriver()->getRating(),
                ];

                // Add driver's vehicle info if available
                if ($ride->getDriver()->getDriver()) {
                    $rideData['driver']['vehicle'] = [
                        'model' => $ride->getDriver()->getDriver()->getVehicleModel(),
                        'color' => $ride->getDriver()->getDriver()->getVehicleColor(),
                        'type' => $ride->getDriver()->getDriver()->getVehicleType(),
                    ];
                }
            } else {
                $rideData['driver'] = null;
            }

            return $rideData;
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
     * Get passenger's current active ride (pending, accepted, or in_progress)
     */
    #[Route('/passenger/current-ride', methods: ['GET'])]
    public function getCurrentRide(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        if ($user->getUserType() !== 'passenger') {
            return new JsonResponse(['error' => 'Not a passenger'], 403);
        }

        // Get the most recent active ride
        $ride = $this->em->createQueryBuilder()
            ->select('r')
            ->from('App\Entity\Ride', 'r')
            ->where('r.passenger = :passenger')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('passenger', $user)
            ->setParameter('statuses', ['pending', 'accepted', 'in_progress'])
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$ride) {
            return new JsonResponse([
                'success' => true,
                'data' => null,
                'message' => 'No active ride'
            ]);
        }

        $rideData = [
            'id' => $ride->getId(),
            'status' => $ride->getStatus(),
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
            ],
        ];

        // Add driver info if assigned
        if ($ride->getDriver()) {
            $rideData['driver'] = [
                'id' => $ride->getDriver()->getId(),
                'name' => $ride->getDriver()->getFullName(),
                'phone' => $ride->getDriver()->getPhone(),
                'rating' => $ride->getDriver()->getRating(),
            ];

            // Add driver's vehicle and location info
            if ($ride->getDriver()->getDriver()) {
                $rideData['driver']['vehicle'] = [
                    'model' => $ride->getDriver()->getDriver()->getVehicleModel(),
                    'color' => $ride->getDriver()->getDriver()->getVehicleColor(),
                    'type' => $ride->getDriver()->getDriver()->getVehicleType(),
                ];
                $rideData['driver']['location'] = [
                    'latitude' => $ride->getDriver()->getDriver()->getCurrentLatitude(),
                    'longitude' => $ride->getDriver()->getDriver()->getCurrentLongitude(),
                ];
            }
        } else {
            $rideData['driver'] = null;
        }

        return new JsonResponse([
            'success' => true,
            'data' => $rideData
        ]);
    }
}
