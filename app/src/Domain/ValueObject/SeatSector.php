<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Shared\StringValueObjectInterface;

final class SeatSector implements StringValueObjectInterface
{
    private string $sector;

    public function __construct(string $sector)
    {
        $trimmed = trim($sector);
        $length = mb_strlen($trimmed);

        if ($length < 1 || $length > 50) {
            throw new \InvalidArgumentException('Seat sector must be between 1 and 50 characters.');
        }

        $this->sector = $trimmed;
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
        return $this->sector;
    }

    public function toValue(): string
    {
        return $this->sector;
    }

    public function equals(StringValueObjectInterface $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->sector === $other->sector;
    }

    public function __toString(): string
    {
        return $this->sector;
    }
}
