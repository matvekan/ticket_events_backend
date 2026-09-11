<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Shared\StringValueObjectInterface;

final class Email implements StringValueObjectInterface
{
    private string $email;

    public function __construct(string $email)
    {
        $email = mb_strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }

        $this->email = $email;
    }

    public static function fromString(string $value): static
    {
        return new self($value);
    }

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->email;
    }

    public function toValue(): string
    {
        return $this->email;
    }

    public function equals(StringValueObjectInterface $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        return $this->email === $other->email;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
