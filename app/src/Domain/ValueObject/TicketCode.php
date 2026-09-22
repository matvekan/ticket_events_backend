<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Shared\StringValueObjectInterface;

final class TicketCode implements StringValueObjectInterface
{
    private string $code;

    public function __construct(string $code)
    {
        if (!preg_match('/^TKT-[A-Z0-9]{8}$/', $code)) {
            throw new \InvalidArgumentException('Ticket code must match pattern TKT-XXXXXXXX (8 uppercase alphanumeric characters).');
        }

        $this->code = $code;
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
        return $this->code;
    }

    public function toValue(): string
    {
        return $this->code;
    }

    public function equals(StringValueObjectInterface $other): bool
    {
        if (!$other instanceof self) {
            return false;
        }

        return $this->code === $other->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
