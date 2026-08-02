<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Name;
use App\Domain\ValueObject\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

#[UniqueEntity(fields: ['email'], message: 'Email already used.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private Uuid $id;
    private Name $name;
    private Email $email;
    private array $roles = [];
    private ?string $password = null;
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

    public function roles(): array
    {
        return $this->roles;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function updateRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function password(): ?string
    {
        return $this->password;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function updatePassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getUserIdentifier(): string
    {
        return $this->email->toValue();
    }

    public function eraseCredentials(): void
    {
    }
}
