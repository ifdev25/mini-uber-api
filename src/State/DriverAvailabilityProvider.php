<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Driver;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DriverAvailabilityProvider implements ProviderInterface
{
    public function __construct(
        private Security $security
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): Driver
    {
        $user = $this->security->getUser();

        if (!$user instanceof User || $user->getUserType() !== 'driver') {
            throw new AccessDeniedHttpException('Unauthorized');
        }

        $driver = $user->getDriver();
        if (!$driver) {
            throw new NotFoundHttpException('Driver profile not found');
        }

        return $driver;
    }
}
