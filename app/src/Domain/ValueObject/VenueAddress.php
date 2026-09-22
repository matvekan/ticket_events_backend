<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Shared\StringValueObjectInterface;

final class VenueAddress implements StringValueObjectInterface
{
    private string $address;

    public function __construct(string $address)
    {
        $trimmed = trim($address);
        $length = mb_strlen($trimmed);

        if ($length < 5 || $length > 255) {
            throw new \InvalidArgumentException('Venue address must be between 5 and 255 characters.');
        }

        $this->address = $trimmed;
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
        return $this->address;
    }

    public function toValue(): string
    {
        return $this->address;
    }

    public function equals(StringValueObjectInterface $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->address === $other->address;
    }

    public function __toString(): string
    {
        return $this->address;
    }
}
