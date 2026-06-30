<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\StringValueObject;

final class Email implements StringValueObject
{
    public function __construct(private string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address.');
        }

        $this->email = $email;
    }

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toValue(): string
    {
        return $this->email;
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
