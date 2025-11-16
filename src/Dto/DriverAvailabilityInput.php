<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class DriverAvailabilityInput
{
    #[Assert\NotNull]
    #[Assert\Type('bool')]
    public bool $isAvailable;
}
