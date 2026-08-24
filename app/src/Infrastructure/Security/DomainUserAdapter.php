<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Bridges Domain User (DDD) to Symfony Security.
 * Keeps Domain clean from framework interfaces.
 */
final class DomainUserAdapter implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private readonly User $user) {}

    public function getUser(): User { return $this->user; }

    public function id(): \App\Domain\ValueObject\UserId { return $this->user->id(); }

    public function getRoles(): array { return $this->user->roles(); }

    public function getPassword(): ?string { return $this->user->password(); }

    public function getUserIdentifier(): string { return $this->user->identifier(); }

    public function eraseCredentials(): void {}

    public function __call(string $name, array $args): mixed
    {
        if (method_exists($this->user, $name)) {
            return $this->user->$name(...$args);
        }
        throw new \BadMethodCallException(sprintf('Method %s not found on DomainUserAdapter.', $name));
    }
}
