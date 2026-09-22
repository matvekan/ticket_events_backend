<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\User;
use App\Domain\ValueObject\UserId;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class DomainUserAdapter implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private readonly User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function id(): UserId
    {
        return $this->user->id();
    }

    public function getRoles(): array
    {
        return $this->user->roles();
    }

    public function getPassword(): ?string
    {
        return $this->user->password();
    }

    public function getUserIdentifier(): string
    {
        $id = $this->user->identifier();
        assert($id !== '');

        return $id;
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @param array<int, mixed> $args
     */
    public function __call(string $name, array $args): mixed
    {
        if (method_exists($this->user, $name)) {
            return $this->user->$name(...$args);
        }

        throw new \BadMethodCallException(\sprintf('Method %s not found on DomainUserAdapter.', $name));
    }
}
