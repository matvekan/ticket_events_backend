<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class DoctrineUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->users->findByEmail(new Email($identifier));

        if (!$user) {
            throw new UserNotFoundException(\sprintf('User with email "%s" not found.', $identifier));
        }

        return new DomainUserAdapter($user);
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        $domainUser = $user instanceof DomainUserAdapter ? $user->getUser() : null;
        $identifier = $domainUser ? $domainUser->identifier() : $user->getUserIdentifier();

        return $this->loadUserByIdentifier($identifier);
    }

    public function supportsClass(string $class): bool
    {
        return $class === DomainUserAdapter::class || is_subclass_of($class, DomainUserAdapter::class);
    }
}
