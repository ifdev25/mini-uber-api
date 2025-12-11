<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Ride;
use App\Service\PricingService;
use App\Service\DriverMatchingService;

class RideProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private PricingService $pricingService,
        private DriverMatchingService $driverMatchingService
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Ride) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // If this is a new ride (POST operation), calculate estimates
        if (!$data->getId() && $operation->getMethod() === 'POST') {
            $estimation = $this->pricingService->calculateEstimate(
                $data->getPickupLatitude(),
                $data->getPickupLongitude(),
                $data->getDropoffLatitude(),
                $data->getDropoffLongitude(),
                $data->getVehicleType() ?? 'standard'
            );

            $data->setEstimatedDistance($estimation['distance']);
            $data->setEstimatedDuration($estimation['duration']);
            $data->setEstimatedPrice($estimation['price']);
            $data->setStatus('pending');

            // Persist the ride
            $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

            // Notify nearby drivers
            $this->driverMatchingService->notifyNearbyDrivers($data);

            return $result;
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
