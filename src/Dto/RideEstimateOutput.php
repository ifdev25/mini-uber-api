<?php

namespace App\Dto;

class RideEstimateOutput
{
    public function __construct(
        public float $distance,
        public float $duration,
        public float $price,
        public string $vehicleType
    ) {}
}
