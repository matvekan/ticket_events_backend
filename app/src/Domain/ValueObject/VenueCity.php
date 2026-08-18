<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\StringValueObject;

final class VenueCity implements StringValueObject
{
    private string $city;

    public function __construct(string $city)
    {
        $trimmed = trim($city);
        $length = mb_strlen($trimmed);

        if ($length < 2 || $length > 100) {
            throw new \InvalidArgumentException('Venue city must be between 2 and 100 characters.');
        }

        $this->city = $trimmed;
    }

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toValue(): string
    {
        return $this->city;
    }

    public function __toString(): string
    {
        return $this->city;
    }
}
