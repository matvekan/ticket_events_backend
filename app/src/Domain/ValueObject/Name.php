<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Shared\StringValueObjectInterface;

final class Name implements StringValueObjectInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $trimmed = trim($name);

        if (mb_strlen($trimmed) < 2 || mb_strlen($trimmed) > 50) {
            throw new \InvalidArgumentException('The name is too short or too long.');
        }

        $this->name = $trimmed;
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
        return $this->name;
    }

    public function toValue(): string
    {
        return $this->name;
    }

    public function equals(StringValueObjectInterface $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        return $this->name === $other->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
