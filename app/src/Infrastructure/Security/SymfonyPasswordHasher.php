<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\Service\PasswordHasherInterface;
use App\Domain\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function hash(User $user, string $plainPassword): string
    {
        return $this->passwordHasher->hashPassword(new DomainUserAdapter($user), $plainPassword);
    }
}
