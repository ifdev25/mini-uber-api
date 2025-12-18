<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Ride;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RideAcceptProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private NotificationService $notificationService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Ride
    {
        if (!$data instanceof Ride) {
            throw new \InvalidArgumentException('Expected Ride entity');
        }

        $driver = $this->security->getUser();

        // Vérifier que c'est un chauffeur
        if (!$driver instanceof User || $driver->getUserType() !== 'driver') {
            throw new AccessDeniedHttpException('Only drivers can accept rides');
        }

        // Vérifier que le chauffeur a un profil driver
        if (!$driver->getDriver()) {
            throw new NotFoundHttpException('Driver profile not found');
        }

        // Vérifier que le chauffeur est vérifié
        if (!$driver->getDriver()->isVerified()) {
            throw new AccessDeniedHttpException('Driver account not verified');
        }

        // Vérifier que le chauffeur est disponible
        if (!$driver->getDriver()->isAvailable()) {
            throw new BadRequestHttpException('Driver is not available');
        }

        if ($data->getStatus() !== 'pending') {
            throw new BadRequestHttpException('Ride already accepted');
        }

        // Vérifier que le type de véhicule du chauffeur correspond
        if ($driver->getDriver()->getVehicleType() !== $data->getVehicleType()) {
            throw new BadRequestHttpException(sprintf(
                'Vehicle type mismatch. Required: %s, Driver has: %s',
                $data->getVehicleType(),
                $driver->getDriver()->getVehicleType()
            ));
        }

        $data->setDriver($driver);
        $data->setStatus('accepted');
        $data->setAcceptedAt(new \DateTimeImmutable());

        // Mettre le chauffeur comme non disponible
        $driver->getDriver()->setIsAvailable(false);

        // Flush pour sauvegarder les changements
        // Note: Mercure publiera automatiquement grâce à mercure: true dans ApiResource
        $this->em->flush();

        // Notifier explicitement le passager avec détails complets (notification push supplémentaire)
        $this->notificationService->notifyPassengerRideAccepted($data);

        return $data;
    }
}
