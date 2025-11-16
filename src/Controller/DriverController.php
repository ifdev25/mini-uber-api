<?php

// src/Controller/DriverController.php

/**
 * @deprecated Ce controller est déprécié. Utilisez les State Processors API Platform à la place.
 * Les endpoints ont été migrés :
 * - PATCH /api/drivers/location -> PATCH /api/drivers/location (DriverLocationProcessor)
 * - PATCH /api/drivers/availability -> PATCH /api/drivers/availability (DriverAvailabilityProcessor)
 * - GET /api/drivers/available -> GET /api/drivers?isAvailable=true&isVerified=true
 *
 * Ce fichier sera supprimé dans une version future.
 * Voir API_ENDPOINTS.md pour la documentation complète.
 */

namespace App\Controller;

use App\Entity\Driver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/drivers')]
class DriverController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

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
                $distance = $this->calculateDistance(
                    $lat,
                    $lng,
                    $driver->getCurrentLatitude(),
                    $driver->getCurrentLongitude()
                );

                if ($distance <= $radius) {
                    $nearbyDrivers[] = [
                        'id' => $driver->getId(),
                        'name' => $driver->getUser()->getFirstName(),
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

    #[Route('/location', methods: ['PATCH'])]
    public function updateLocation(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$user || $user->getUserType() !== 'driver') {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $driver = $user->getDriverProfile();
        $driver->setCurrentLatitude($data['lat']);
        $driver->setCurrentLongitude($data['lng']);

        $this->em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/availability', methods: ['PATCH'])]
    public function toggleAvailability(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        if (!$user || $user->getUserType() !== 'driver') {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $driver = $user->getDriverProfile();
        $driver->setIsAvailable($data['isAvailable']);

        $this->em->flush();

        return new JsonResponse([
            'isAvailable' => $driver->isAvailable()
        ]);
    }

    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        // Formule de Haversine
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
