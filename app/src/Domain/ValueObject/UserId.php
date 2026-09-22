<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Shared\StringValueObjectInterface;

final readonly class UserId implements StringValueObjectInterface
{
    public function __construct(private string $value)
    {
        if (!self::isValidUuid($value)) {
            throw new \InvalidArgumentException(\sprintf('Invalid UserId UUID: %s', $value));
        }
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
        return $this->value;
    }

    public function equals(StringValueObjectInterface $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function isValidUuid(string $value): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }
}
