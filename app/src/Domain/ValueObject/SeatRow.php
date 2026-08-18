<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\StringValueObject;

final class SeatRow implements StringValueObject
{
    private string $row;

    public function __construct(string $row)
    {
        $trimmed = trim($row);
        $length = mb_strlen($trimmed);

        if ($length < 1 || $length > 10) {
            throw new \InvalidArgumentException('Seat row must be between 1 and 10 characters.');
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $trimmed)) {
            throw new \InvalidArgumentException('Seat row may only contain letters and digits.');
        }

        $this->row = $trimmed;
    }

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toValue(): string
    {
        return $this->row;
    }

    public function __toString(): string
    {
        return $this->row;
    }
}
