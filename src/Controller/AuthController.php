<?php

// src/Controller/AuthController.php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstname($data['firstName']);
        $user->setLastname($data['lastName']);
        $user->setPhone($data['phone']);
        $user->setUsertype($data['userType'] ?? 'passenger');

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstname(),
                'lastName' => $user->getLastname(),
                'userType' => $user->getUsertype()
            ],
            'token' => $token
        ], 201);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // Géré automatiquement par LexikJWTAuthenticationBundle
        // Configuration dans security.yaml
        return new JsonResponse(['message' => 'Login handled by JWT']);
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstname(),
            'lastName' => $user->getLastname(),
            'phone' => $user->getPhone(),
            'userType' => $user->getUsertype(),
            'rating' => $user->getRating(),
            'totalRides' => $user->getTotalRides(),
            'driverProfile' => $user->getDriver() ? [
                'vehicleModel' => $user->getDriver()->getVehiculeModel(),
                'vehicleColor' => $user->getDriver()->getVehiculeColor(),
                'vehicleType' => $user->getDriver()->getVehiculeType(),
                'isAvailable' => $user->getDriver()->isAvailable()
            ] : null
        ]);
    }
}