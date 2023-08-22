<?php

namespace App\Security;

use App\Repository\ApiTokenRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private ApiTokenRepository $repository
    ) {
    }

    public function getUserBadgeFrom(string $apiToken): UserBadge
    {
        $apiToken = $this->repository->findOneBy(['token' => $apiToken]);

        if (null === $apiToken/* || !$apiToken->isValid()*/) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        if (!$apiToken->isValid()) {
            throw new BadCredentialsException('Experied token');
        }

        return new UserBadge($apiToken->getUser()->getUserIdentifier());
    }
}