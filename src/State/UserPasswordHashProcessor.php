<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserPasswordHashProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof User) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Hash the password if it's a plain password
        if ($data->getPassword() && !$this->isPasswordHashed($data->getPassword())) {
            $hashedPassword = $this->passwordHasher->hashPassword($data, $data->getPassword());
            $data->setPassword($hashedPassword);
        }

        // Set default role if not set
        if (empty($data->getRoles())) {
            $data->setRoles(['ROLE_USER']);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function isPasswordHashed(string $password): bool
    {
        // Bcrypt hashes are 60 characters long and start with $2y$
        return strlen($password) === 60 && str_starts_with($password, '$2y$');
    }
}
