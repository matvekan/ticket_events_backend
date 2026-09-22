<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Shared\IntegerValueObjectInterface;

final class SeatNumber implements IntegerValueObjectInterface
{
    private int $number;

    public function __construct(int $number)
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

    public function toInt(): int
    {
        return $this->number;
    }

    public function toValue(): int
    {
        return $this->number;
    }

    public function equals(IntegerValueObjectInterface $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->number === $other->number;
    }

    public function __toString(): string
    {
        return (string) $this->number;
    }
}
