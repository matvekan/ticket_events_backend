<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\StringValueObject;

final class SeatSector implements StringValueObject
{
    public function __construct(private string $sector)
    {
        $trimmed = trim($sector);
        $length = mb_strlen($trimmed);

        if ($length < 1 || $length > 50) {
            throw new \InvalidArgumentException('Seat sector must be between 1 and 50 characters.');
        }

        $this->sector = $trimmed;
    }

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toValue(): string
    {
        return $this->sector;
    }

    public function __toString(): string
    {
        return $this->sector;
    }
}
