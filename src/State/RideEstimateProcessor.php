<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\RideEstimateInput;
use App\Dto\RideEstimateOutput;
use App\Service\PricingService;

class RideEstimateProcessor implements ProcessorInterface
{
    public function __construct(
        private PricingService $pricingService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): RideEstimateOutput
    {
        if (!$data instanceof RideEstimateInput) {
            throw new \InvalidArgumentException('Expected RideEstimateInput');
        }

        $estimation = $this->pricingService->calculateEstimate(
            $data->pickupLat,
            $data->pickupLng,
            $data->dropoffLat,
            $data->dropoffLng,
            $data->vehicleType
        );

        return new RideEstimateOutput(
            distance: $estimation['distance'],
            duration: $estimation['duration'],
            price: $estimation['price'],
            vehicleType: $data->vehicleType
        );
    }
}
