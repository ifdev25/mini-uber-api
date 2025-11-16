<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Dto\RideEstimateInput;
use App\Dto\RideEstimateOutput;
use App\State\RideEstimateProcessor;

#[ApiResource(
    shortName: 'RideEstimate',
    operations: [
        new Post(
            uriTemplate: '/ride-estimates',
            input: RideEstimateInput::class,
            output: RideEstimateOutput::class,
            processor: RideEstimateProcessor::class,
            description: 'Calculate ride price estimate'
        )
    ]
)]
class RideEstimate
{
    // Cette classe est juste un marqueur pour l'API Resource
    // Les données sont gérées par les DTOs
}
