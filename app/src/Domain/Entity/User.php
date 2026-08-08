<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private Uuid $id;
    private Name $name;
    private Email $email;
    private array $roles = [];
    private ?string $password = null;
    private ?string $resetPasswordTokenHash = null;
    private ?\DateTimeImmutable $resetPasswordTokenExpiresAt = null;
    private Collection $orders;

    private function __construct(Name $name, Email $email)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->email = $email;
        $this->roles = [Role::User->value];
        $this->orders = new ArrayCollection();
    }

    public static function create(Name $name, Email $email): self
    {
        return new self($name, $email);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function updateRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function updatePassword(?string $password): void
    {
        $this->password = $password;
    }

    public function setPasswordResetToken(string $tokenHash, \DateTimeImmutable $expiresAt): void
    {
        $this->resetPasswordTokenHash = $tokenHash;
        $this->resetPasswordTokenExpiresAt = $expiresAt;
    }

    public function clearPasswordResetToken(): void
    {
        $this->resetPasswordTokenHash = null;
        $this->resetPasswordTokenExpiresAt = null;
    }

    public function isPasswordResetTokenValid(\DateTimeImmutable $now): bool
    {
        return $this->resetPasswordTokenHash !== null
            && $this->resetPasswordTokenExpiresAt !== null
            && $this->resetPasswordTokenExpiresAt > $now;
    }

    public function getUserIdentifier(): string
    {
        return $this->email->toValue();
    }

    public function eraseCredentials(): void
    {
    }
}
