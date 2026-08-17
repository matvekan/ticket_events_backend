<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\StringValueObject;

final class EventTitle implements StringValueObject
{
    public function __construct(private string $title)
    {
        $trimmed = trim($title);
        $length = mb_strlen($trimmed);

        if ($length < 3 || $length > 100) {
            throw new \InvalidArgumentException('Event title must be between 3 and 100 characters.');
        }

        $this->title = $trimmed;
    }

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toValue(): string
    {
        return $this->title;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
