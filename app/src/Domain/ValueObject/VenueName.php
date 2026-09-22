<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Shared\StringValueObjectInterface;

final class VenueName implements StringValueObjectInterface
{
    private string $name;

    public function __construct(string $name)
    {
        $trimmed = trim($name);
        $length = mb_strlen($trimmed);

        if ($length < 2 || $length > 255) {
            throw new \InvalidArgumentException('Venue name must be between 2 and 255 characters.');
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
        if (!$other instanceof self) {
            return false;
        }

        return $this->name === $other->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
