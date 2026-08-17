<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\IntegerValueObject;

final class SeatNumber implements IntegerValueObject
{
    public function __construct(private int $number)
    {
        if ($number < 1) {
            throw new \InvalidArgumentException('Seat number must be positive.');
        }

        $this->number = $number;
    }

    public static function fromValue(int $value): static
    {
        return new self($value);
    }

    public function toValue(): int
    {
        return $this->number;
    }
}
