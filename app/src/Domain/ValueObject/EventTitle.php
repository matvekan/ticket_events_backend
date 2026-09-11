<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Shared\StringValueObjectInterface;

final class EventTitle implements StringValueObjectInterface
{
    private string $title;

    public function __construct(string $title)
    {
        $trimmed = trim($title);
        $length = mb_strlen($trimmed);

        if ($length < 3 || $length > 100) {
            throw new \InvalidArgumentException('Event title must be between 3 and 100 characters.');
        }

        $this->title = $trimmed;
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
        return $this->title;
    }

    public function toValue(): string
    {
        return $this->title;
    }

    public function equals(StringValueObjectInterface $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        return $this->title === $other->title;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
