<?php

// src/Controller/RideController.php

/**
 * @deprecated Ce controller est déprécié. Utilisez les State Processors API Platform à la place.
 * Les endpoints ont été migrés :
 * - POST /api/rides/estimate -> POST /api/ride-estimates (RideEstimateProcessor)
 * - POST /api/rides/request -> POST /api/rides (RideProcessor)
 * - POST /api/rides/{id}/accept -> POST /api/rides/{id}/accept (RideAcceptProcessor)
 * - PATCH /api/rides/{id}/status -> PATCH /api/rides/{id}/status (RideStatusProcessor)
 *
 * Ce fichier sera supprimé dans une version future.
 * Voir API_ENDPOINTS.md pour la documentation complète.
 */

namespace App\Controller;

use App\Entity\Ride;
use App\Entity\User;
use App\Service\PricingService;
use App\Service\DriverMatchingService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/rides')]
class RideController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private PricingService $pricingService,
        private DriverMatchingService $driverMatching,
        private NotificationService $notificationService
    ) {}

    #[Route('/estimate', methods: ['POST'])]
    public function estimateRide(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $estimation = $this->pricingService->calculateEstimate(
            $data['pickupLat'],
            $data['pickupLng'],
            $data['dropoffLat'],
            $data['dropoffLng'],
            $data['vehicleType'] ?? 'standard'
        );

        return new JsonResponse($estimation);
    }

    #[Route('/request', methods: ['POST'])]
    public function requestRide(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $ride = new Ride();
        $ride->setPassenger($user);
        $ride->setPickUpLatitude($data['pickupLat']);
        $ride->setPickUpLongitude($data['pickupLng']);
        $ride->setPickUpAddress($data['pickupAddress']);
        $ride->setDropoffLatitude($data['dropoffLat']);
        $ride->setDropoffLongitude($data['dropoffLng']);
        $ride->setDropoffAddress($data['dropoffAddress']);
        $ride->setVehiculeType($data['vehicleType'] ?? 'standard');

        $estimation = $this->pricingService->calculateEstimate(
            $data['pickupLat'],
            $data['pickupLng'],
            $data['dropoffLat'],
            $data['dropoffLng'],
            $data['vehicleType'] ?? 'standard'
        );

        $ride->setEstimatedDistance($estimation['distance']);
        $ride->setEstimatedDuration($estimation['duration']);
        $ride->setEstimatedPrice($estimation['price']);
        $ride->setStatus('pending');

        $this->em->persist($ride);
        $this->em->flush();

        // Notifier les chauffeurs disponibles à proximité
        $this->driverMatching->notifyNearbyDrivers($ride);

        return new JsonResponse([
            'id' => $ride->getId(),
            'status' => $ride->getStatus(),
            'estimatedPrice' => $ride->getEstimatedPrice(),
            'estimatedDuration' => $ride->getEstimatedDuration()
        ], 201);
    }

    #[Route('/{id}/accept', methods: ['POST'])]
    public function acceptRide(int $id): JsonResponse
    {
        $driver = $this->getUser();

        // Vérifier que c'est un chauffeur
        if (!$driver instanceof User || $driver->getUserType() !== 'driver') {
            return new JsonResponse(['error' => 'Only drivers can accept rides'], 403);
        }

        // Vérifier que le chauffeur a un profil driver
        if (!$driver->getDriver()) {
            return new JsonResponse(['error' => 'Driver profile not found'], 404);
        }

        // Vérifier que le chauffeur est vérifié
        if (!$driver->getDriver()->isVerified()) {
            return new JsonResponse(['error' => 'Driver account not verified'], 403);
        }

        // Vérifier que le chauffeur est disponible
        if (!$driver->getDriver()->isAvailable()) {
            return new JsonResponse(['error' => 'Driver is not available'], 400);
        }

        $ride = $this->em->getRepository(Ride::class)->find($id);

        if (!$ride) {
            return new JsonResponse(['error' => 'Ride not found'], 404);
        }

        if ($ride->getStatus() !== 'pending') {
            return new JsonResponse(['error' => 'Ride already accepted'], 400);
        }

        // Vérifier que le type de véhicule du chauffeur correspond
        if ($driver->getDriver()->getVehiculeType() !== $ride->getVehiculeType()) {
            return new JsonResponse([
                'error' => 'Vehicle type mismatch',
                'required' => $ride->getVehiculeType(),
                'driver_has' => $driver->getDriver()->getVehiculeType()
            ], 400);
        }

        $ride->setDriver($driver);
        $ride->setStatus('accepted');
        $ride->setAcceptedAt(new \DateTimeImmutable());

        // Mettre le chauffeur comme non disponible
        $driver->getDriver()->setIsAvailable(false);

        $this->em->flush();

        // Notifier le passager que sa course a été acceptée
        $this->notificationService->notifyPassengerRideAccepted($ride);

        return new JsonResponse([
            'id' => $ride->getId(),
            'status' => $ride->getStatus(),
            'driver' => [
                'name' => $driver->getFirstname() . ' ' . $driver->getLastname(),
                'rating' => $driver->getRating(),
                'vehicle' => $driver->getDriver()->getVehiculeModel()
            ]
        ]);
    }

    #[Route('/{id}/status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        $ride = $this->em->getRepository(Ride::class)->find($id);
        
        if (!$ride) {
            return new JsonResponse(['error' => 'Ride not found'], 404);
        }

        // Vérifier que l'utilisateur est le chauffeur de la course
        if ($ride->getDriver() !== $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $newStatus = $data['status'];
        $ride->setStatus($newStatus);

        if ($newStatus === 'in_progress') {
            $ride->setStartedAt(new \DateTimeImmutable());
            // Notifier le passager que la course a commencé
            $this->notificationService->notifyPassengerRideStarted($ride);
        } elseif ($newStatus === 'completed') {
            $ride->setCompletedAt(new \DateTimeImmutable());
            $ride->setFinalPrice($ride->getEstimatedPrice());

            // Rendre le chauffeur disponible à nouveau
            $user->getDriver()->setIsAvailable(true);

            // Incrémenter le nombre de courses
            $ride->getPassenger()->setTotalRides(($ride->getPassenger()->getTotalRides() ?? 0) + 1);
            $user->setTotalRides(($user->getTotalRides() ?? 0) + 1);

            // Notifier le passager que la course est terminée
            $this->notificationService->notifyPassengerRideCompleted($ride);
        }

        $this->em->flush();

        return new JsonResponse([
            'id' => $ride->getId(),
            'status' => $ride->getStatus()
        ]);
    }

    #[Route('/history', methods: ['GET'])]
    public function getHistory(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $rides = $user->getUsertype() === 'driver'
            ? $user->getRidesAsDriver()
            : $user->getRidesAsPassenger();

        $history = [];
        foreach ($rides as $ride) {
            $history[] = [
                'id' => $ride->getId(),
                'status' => $ride->getStatus(),
                'pickupAddress' => $ride->getPickUpAddress(),
                'dropoffAddress' => $ride->getDropoffAddress(),
                'price' => $ride->getFinalPrice() ?? $ride->getEstimatedPrice(),
                'date' => $ride->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse($history);
    }
}