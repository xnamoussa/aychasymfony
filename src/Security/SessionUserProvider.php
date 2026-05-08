<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

/**
 * @implements UserProviderInterface<SessionUser>
 */
class SessionUserProvider implements UserProviderInterface
{
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        if ($identifier === 'admin_user') {
            return new SessionUser(2, $identifier, ['ROLE_ADMIN', 'ROLE_USER']);
        }
        
        return new SessionUser(1, 'standard_user', ['ROLE_USER']);
    }

    public function refreshUser(UserInterface $user): SessionUser
    {
        if (!$user instanceof SessionUser) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return SessionUser::class === $class || is_subclass_of($class, SessionUser::class);
    }
}
