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

class RideCancelProcessor implements ProcessorInterface
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

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Unauthorized');
        }

        // Vérifier que la course peut être annulée
        $allowedStatuses = ['pending', 'accepted'];
        if (!in_array($data->getStatus(), $allowedStatuses)) {
            throw new BadRequestHttpException(
                sprintf('Cannot cancel ride with status "%s". Only pending or accepted rides can be cancelled.', $data->getStatus())
            );
        }

        // Vérifier que l'utilisateur est soit le passager soit le chauffeur
        $isPassenger = $data->getPassenger() === $user;
        $isDriver = $data->getDriver() === $user;

        if (!$isPassenger && !$isDriver) {
            throw new AccessDeniedHttpException('Only the passenger or assigned driver can cancel this ride');
        }

        // Mettre à jour le statut
        $data->setStatus('cancelled');

        // Si un chauffeur était assigné, le rendre disponible à nouveau
        if ($data->getDriver() && $data->getDriver()->getDriver()) {
            $data->getDriver()->getDriver()->setIsAvailable(true);
        }

        $this->em->flush();

        // Notifier l'autre partie
        if ($isPassenger && $data->getDriver()) {
            // Le passager a annulé, notifier le chauffeur
            $this->notificationService->notifyDriverRideUpdate(
                $data,
                'The passenger has cancelled the ride'
            );
        } elseif ($isDriver) {
            // Le chauffeur a annulé, notifier le passager
            $this->notifyPassengerRideCancelled($data);
        }

        return $data;
    }

    private function notifyPassengerRideCancelled(Ride $ride): void
    {
        // TODO: Implémenter la notification au passager
        // Pour l'instant, on utilise la méthode générique
        if ($ride->getDriver()) {
            $this->notificationService->notifyDriverRideUpdate(
                $ride,
                'Ride cancelled'
            );
        }
    }
}
