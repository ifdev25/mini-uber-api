<?php

namespace App\Service;

use App\Entity\Ride;
use App\Entity\User;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationService
{
    public function __construct(
        private HubInterface $hub
    ) {}

    /**
     * Notify nearby drivers about a new ride request
     */
    public function notifyDriversAboutNewRide(Ride $ride, array $nearbyDrivers): void
    {
        $data = [
            'type' => 'new_ride',
            'ride' => [
                'id' => $ride->getId(),
                'pickupAddress' => $ride->getPickUpAddress(),
                'dropoffAddress' => $ride->getDropoffAddress(),
                'estimatedPrice' => $ride->getEstimatedPrice(),
                'estimatedDistance' => $ride->getEstimatedDistance(),
                'vehicleType' => $ride->getVehicleType(),
                'passenger' => [
                    'name' => $ride->getPassenger()->getFullName(),
                    'rating' => $ride->getPassenger()->getRating()
                ]
            ]
        ];

        // Send to each nearby driver
        foreach ($nearbyDrivers as $driver) {
            $topic = sprintf('drivers/%d', $driver->getId());
            $this->publish($topic, $data);
        }
    }

    /**
     * Notify passenger that a driver accepted their ride
     * Publie sur DEUX topics pour notifications instantanées
     */
    public function notifyPassengerRideAccepted(Ride $ride): void
    {
        $data = [
            'type' => 'ride_accepted',
            'ride' => [
                'id' => $ride->getId(),
                'status' => $ride->getStatus(),
                'acceptedAt' => $ride->getAcceptedAt()?->format('c'),
                'driver' => [
                    'id' => $ride->getDriver()->getId(),
                    'name' => $ride->getDriver()->getFullName(),
                    'rating' => $ride->getDriver()->getRating(),
                    'phone' => $ride->getDriver()->getPhone(),
                    'vehicle' => [
                        'model' => $ride->getDriver()->getDriver()->getVehicleModel(),
                        'color' => $ride->getDriver()->getDriver()->getVehicleColor(),
                        'type' => $ride->getDriver()->getDriver()->getVehicleType(),
                        'currentLocation' => [
                            'lat' => $ride->getDriver()->getDriver()->getCurrentLatitude(),
                            'lng' => $ride->getDriver()->getDriver()->getCurrentLongitude()
                        ]
                    ]
                ]
            ]
        ];

        // Publication 1: Topic utilisateur (pour notifications générales)
        $userTopic = sprintf('users/%d', $ride->getPassenger()->getId());
        $this->publish($userTopic, $data);

        // Publication 2: Topic de la course (pour suivi en temps réel)
        // Format API Platform pour compatibilité avec mercure: true
        $rideTopic = sprintf('/api/rides/%d', $ride->getId());
        $this->publish($rideTopic, $data);
    }

    /**
     * Notify passenger that the ride has started
     */
    public function notifyPassengerRideStarted(Ride $ride): void
    {
        $data = [
            'type' => 'ride_started',
            'ride' => [
                'id' => $ride->getId(),
                'status' => $ride->getStatus(),
                'startedAt' => $ride->getStartedAt()?->format('Y-m-d H:i:s')
            ]
        ];

        $topic = sprintf('users/%d', $ride->getPassenger()->getId());
        $this->publish($topic, $data);
    }

    /**
     * Notify passenger that the ride is completed
     */
    public function notifyPassengerRideCompleted(Ride $ride): void
    {
        $data = [
            'type' => 'ride_completed',
            'ride' => [
                'id' => $ride->getId(),
                'status' => $ride->getStatus(),
                'finalPrice' => $ride->getFinalPrice(),
                'completedAt' => $ride->getCompletedAt()?->format('Y-m-d H:i:s')
            ],
            // Information pour la redirection côté frontend
            'action' => [
                'type' => 'redirect',
                'route' => '/rides/history', // Route pour l'historique des courses
                'userType' => 'passenger'
            ]
        ];

        $topic = sprintf('users/%d', $ride->getPassenger()->getId());
        $this->publish($topic, $data);
    }

    /**
     * Notify driver about ride status changes
     */
    public function notifyDriverRideUpdate(Ride $ride, string $message): void
    {
        if (!$ride->getDriver()) {
            return;
        }

        $data = [
            'type' => 'ride_update',
            'message' => $message,
            'ride' => [
                'id' => $ride->getId(),
                'status' => $ride->getStatus()
            ]
        ];

        $topic = sprintf('drivers/%d', $ride->getDriver()->getId());
        $this->publish($topic, $data);
    }

    /**
     * Update driver location in real-time
     */
    public function updateDriverLocation(User $driver, float $lat, float $lng): void
    {
        if ($driver->getUsertype() !== 'driver') {
            return;
        }

        $data = [
            'type' => 'location_update',
            'location' => [
                'lat' => $lat,
                'lng' => $lng
            ]
        ];

        $topic = sprintf('drivers/%d/location', $driver->getId());
        $this->publish($topic, $data);
    }

    /**
     * Publish a message to a Mercure topic
     * Topics commençant par / sont des topics API Platform
     * Autres topics sont des topics custom (users/X, drivers/X)
     */
    private function publish(string $topic, array $data): void
    {
        // Si le topic commence par /, c'est un topic API Platform (ex: /api/rides/123)
        // Sinon, c'est un topic custom qu'on préfixe (ex: users/48 -> http://localhost:3000/users/48)
        $fullTopic = str_starts_with($topic, '/')
            ? 'http://localhost:8080' . $topic  // Topic API Platform
            : 'http://localhost:3000/' . $topic; // Topic custom

        $update = new Update(
            $fullTopic,
            json_encode($data)
        );

        $this->hub->publish($update);
    }
}
