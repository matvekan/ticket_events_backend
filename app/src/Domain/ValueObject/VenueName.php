<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\StringValueObject;

final class VenueName implements StringValueObject
{
    public function __construct(private string $name)
    {
        $trimmed = trim($name);
        $length = mb_strlen($trimmed);

        if ($length < 2 || $length > 255) {
            throw new \InvalidArgumentException('Venue name must be between 2 and 255 characters.');
        }

        $this->name = $trimmed;
    }

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toValue(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
