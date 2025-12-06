<?php

// src/Controller/AuthController.php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailService;
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
        private JWTTokenManagerInterface $jwtManager,
        private EmailService $emailService
    ) {}

    #[Route('/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données requises
        $requiredFields = ['email', 'password', 'firstName', 'lastName', 'phone'];
        $errors = [];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "Le champ $field est requis.";
            }
        }

        // Validation de l'email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "L'email n'est pas valide.";
        }

        // Validation du mot de passe
        if (!empty($data['password']) && strlen($data['password']) < 6) {
            $errors['password'] = "Le mot de passe doit contenir au moins 6 caractères.";
        }

        // Validation du téléphone
        if (!empty($data['phone']) && !preg_match('/^\+?[0-9]{10,15}$/', $data['phone'])) {
            $errors['phone'] = "Le numéro de téléphone n'est pas valide.";
        }

        if (!empty($errors)) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Erreur de validation',
                'violations' => $errors
            ], 422);
        }

        // Vérifier si l'email existe déjà
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Un compte avec cet email existe déjà.',
                'code' => 409
            ], 409);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstname($data['firstName']);
        $user->setLastname($data['lastName']);
        $user->setPhone($data['phone']);
        $user->setUsertype($data['userType'] ?? 'passenger');

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // Generate verification token
        $verificationToken = bin2hex(random_bytes(32));
        $user->setVerificationToken($verificationToken);
        $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));
        $user->setIsVerified(false);

        $this->em->persist($user);
        $this->em->flush();

        // Send verification email
        $this->emailService->sendVerificationEmail(
            $user->getEmail(),
            $verificationToken,
            $user->getFullName()
        );

        $token = $this->jwtManager->create($user);

        return new JsonResponse([
            'message' => 'Inscription réussie. Veuillez vérifier votre email pour activer votre compte.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstname(),
                'lastName' => $user->getLastname(),
                'userType' => $user->getUsertype(),
                'isVerified' => $user->isVerified()
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
            'isVerified' => $user->isVerified(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
            'driverProfile' => $user->getDriver() ? [
                'id' => $user->getDriver()->getId(),
                'vehicleModel' => $user->getDriver()->getVehicleModel(),
                'vehicleColor' => $user->getDriver()->getVehicleColor(),
                'vehicleType' => $user->getDriver()->getVehicleType(),
                'isAvailable' => $user->getDriver()->isAvailable(),
                'currentLatitude' => $user->getDriver()->getCurrentLatitude(),
                'currentLongitude' => $user->getDriver()->getCurrentLongitude()
            ] : null
        ]);
    }

    #[Route('/verify-email', methods: ['POST'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return new JsonResponse(['error' => 'Token manquant'], 400);
        }

        // Find user by verification token
        $user = $this->em->getRepository(User::class)->findOneBy([
            'verificationToken' => $token
        ]);

        if (!$user) {
            return new JsonResponse(['error' => 'Token invalide'], 400);
        }

        // Check if token has expired
        $now = new \DateTimeImmutable();
        if ($user->getVerificationTokenExpiresAt() && $user->getVerificationTokenExpiresAt() < $now) {
            return new JsonResponse(['error' => 'Le token a expiré'], 400);
        }

        // Verify the user
        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $user->setVerificationTokenExpiresAt(null);

        $this->em->flush();

        return new JsonResponse([
            'message' => 'Email vérifié avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'isVerified' => $user->isVerified()
            ]
        ], 200);
    }

    #[Route('/resend-verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['error' => 'Email manquant'], 400);
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        if ($user->isVerified()) {
            return new JsonResponse(['error' => 'Email déjà vérifié'], 400);
        }

        // Generate new verification token
        $token = bin2hex(random_bytes(32));
        $user->setVerificationToken($token);
        $user->setVerificationTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

        $this->em->flush();

        // Send verification email
        $this->emailService->sendVerificationEmail(
            $user->getEmail(),
            $token,
            $user->getFullName()
        );

        return new JsonResponse([
            'message' => 'Email de vérification renvoyé'
        ], 200);
    }
}