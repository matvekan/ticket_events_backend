<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\StringValueObject;

final class EventDescription implements StringValueObject
{
    private string $description;

    public function __construct(string $description)
    {
        $trimmed = trim($description);
        $length = mb_strlen($trimmed);

        if ($length < 10 || $length > 5000) {
            throw new \InvalidArgumentException('Event description must be between 10 and 5000 characters.');
        }

        $this->description = $trimmed;
    }

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toValue(): string
    {
        return $this->description;
    }

    public function __toString(): string
    {
        return $this->description;
    }
}
