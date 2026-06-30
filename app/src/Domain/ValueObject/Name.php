<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\StringValueObject;

final class Name implements StringValueObject
{
    public function __construct(private string $name)
    {
        $trimmed = trim($name);

        if (mb_strlen($trimmed) < 2 || mb_strlen($trimmed) > 50) {
            throw new \InvalidArgumentException('The name is too short or too long.');
        }

        $this->name = $trimmed;
    }

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toValue(): string
    {
        return (string) $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
