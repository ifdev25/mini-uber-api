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

class RideStatusProcessor implements ProcessorInterface
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

        $user = $this->security->getUser();

        // Vérifier que l'utilisateur est le chauffeur de la course
        if ($data->getDriver() !== $user) {
            throw new AccessDeniedHttpException('Unauthorized');
        }

        $newStatus = $data->getStatus();

        if ($newStatus === 'in_progress') {
            $data->setStartedAt(new \DateTimeImmutable());
            // Notifier le passager que la course a commencé
            $this->notificationService->notifyPassengerRideStarted($data);
        } elseif ($newStatus === 'completed') {
            $data->setCompletedAt(new \DateTimeImmutable());
            $data->setFinalPrice($data->getEstimatedPrice());

            // Rendre le chauffeur disponible à nouveau
            if ($user instanceof User && $user->getDriver()) {
                $user->getDriver()->setIsAvailable(true);
            }

            // Incrémenter le nombre de courses
            $data->getPassenger()->setTotalRides(($data->getPassenger()->getTotalRides() ?? 0) + 1);
            if ($user instanceof User) {
                $user->setTotalRides(($user->getTotalRides() ?? 0) + 1);
            }

            // Notifier le passager que la course est terminée
            $this->notificationService->notifyPassengerRideCompleted($data);
        }

        $this->em->flush();

        return $data;
    }
}
