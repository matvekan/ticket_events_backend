<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Role;
use App\Domain\ValueObject\UserId;

class User
{
    private string $id;
    private Name $name;
    private Email $email;
    private array $roles = [];
    private ?string $password = null;
    private ?string $resetPasswordTokenHash = null;
    private ?\DateTimeImmutable $resetPasswordTokenExpiresAt = null;

    private function __construct(UserId $id, Name $name, Email $email)
    {
        $this->id = $id->toString();
        $this->name = $name;
        $this->email = $email;
        $this->roles = [Role::User->value];
    }

    public static function create(UserId $id, Name $name, Email $email): self
    {
        return new self($id, $name, $email);
    }

    public function id(): UserId
    {
        return new UserId($this->id);
    }

    public function rawId(): string
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

    public function roles(): array
    {
        return $this->roles;
    }

    public function changeRoles(array $roles): void
    {
        foreach ($roles as $role) {
            if (!is_string($role) || Role::tryFrom($role) === null) {
                throw new BusinessRuleViolationException(sprintf('Invalid role "%s".', is_scalar($role) ? (string) $role : get_debug_type($role)));
            }
        }

        $this->roles = array_values(array_unique($roles));
    }

    public function password(): ?string
    {
        return $this->password;
    }

    public function changePassword(?string $password): void
    {
        $this->password = $password;
    }

    public function resetTokenHash(): ?string
    {
        return $this->resetPasswordTokenHash;
    }

    public function resetTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetPasswordTokenExpiresAt;
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

    public function identifier(): string
    {
        return $this->email->toValue();
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier();
    }
}
