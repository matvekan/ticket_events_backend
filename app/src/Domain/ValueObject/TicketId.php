<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\StringValueObject;

final readonly class TicketId implements StringValueObject
{
    public function __construct(private string $value)
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            throw new \InvalidArgumentException(sprintf('Invalid TicketId UUID: %s', $value));
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public static function fromValue(string $value): self
    {
        return new self($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function toValue(): string
    {
        return $this->value;
    }

    public function toRfc4122(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
