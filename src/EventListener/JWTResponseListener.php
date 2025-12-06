<?php

namespace App\EventListener;

use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class JWTResponseListener
{
    public function __construct(
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private int $ttl
    ) {}

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        // Generate refresh token
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl(
            $user,
            $this->ttl
        );

        $this->refreshTokenManager->save($refreshToken);

        // Add refresh token to response
        $data['refreshToken'] = $refreshToken->getRefreshToken();
        $data['refresh_token_expiration'] = $refreshToken->getValid()->getTimestamp();

        $event->setData($data);
    }
}
