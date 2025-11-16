<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class DriverLocationInput
{
    #[Assert\NotBlank]
    #[Assert\Type('float')]
    public float $lat;

    #[Assert\NotBlank]
    #[Assert\Type('float')]
    public float $lng;
}
