<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RideStatusInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['in_progress', 'completed', 'cancelled'])]
    public string $status;
}
