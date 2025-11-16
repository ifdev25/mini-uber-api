<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RideEstimateInput
{
    #[Assert\NotBlank]
    #[Assert\Type('float')]
    public float $pickupLat;

    #[Assert\NotBlank]
    #[Assert\Type('float')]
    public float $pickupLng;

    #[Assert\NotBlank]
    #[Assert\Type('float')]
    public float $dropoffLat;

    #[Assert\NotBlank]
    #[Assert\Type('float')]
    public float $dropoffLng;

    #[Assert\Choice(choices: ['standard', 'comfort', 'premium', 'xl'])]
    public string $vehicleType = 'standard';
}
