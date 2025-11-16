<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Driver;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DriverLocationProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private NotificationService $notificationService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Driver
    {
        if (!$data instanceof Driver) {
            throw new \InvalidArgumentException('Expected Driver entity');
        }

        $user = $this->security->getUser();

        if (!$user instanceof User || $user->getUserType() !== 'driver') {
            throw new AccessDeniedHttpException('Unauthorized');
        }

        $driver = $user->getDriver();
        if (!$driver) {
            throw new NotFoundHttpException('Driver profile not found');
        }

        // Mettre à jour la position
        $driver->setCurrentLatitude($data->getCurrentLatitude());
        $driver->setCurrentLongitude($data->getCurrentLongitude());

        $this->em->flush();

        // Notifier en temps réel de la mise à jour de position
        $this->notificationService->updateDriverLocation(
            $user,
            $data->getCurrentLatitude(),
            $data->getCurrentLongitude()
        );

        return $driver;
    }
}
