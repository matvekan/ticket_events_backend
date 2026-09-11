<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Shared\StringValueObjectInterface;

final class EventDescription implements StringValueObjectInterface
{
    private string $description;

    public function __construct(string $description)
    {
        $trimmed = trim($description);
        $length = mb_strlen($trimmed);

        if ($length < 10 || $length > 5000) {
            throw new \InvalidArgumentException('Event description must be between 10 and 5000 characters.');
        }

        $this->description = $trimmed;
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
        return $this->description;
    }

    public function toValue(): string
    {
        return $this->description;
    }

    public function equals(StringValueObjectInterface $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        return $this->description === $other->description;
    }

    public function __toString(): string
    {
        return $this->description;
    }
}
