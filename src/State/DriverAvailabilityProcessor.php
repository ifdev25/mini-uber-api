<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Driver;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DriverAvailabilityProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security
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

        // Mettre à jour la disponibilité
        $driver->setIsAvailable($data->isAvailable());

        $this->em->flush();

        return $driver;
    }
}
