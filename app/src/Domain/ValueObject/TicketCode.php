<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Yokai\DoctrineValueObject\StringValueObject;

final class TicketCode implements StringValueObject
{
    private string $code;

    public function __construct(string $code)
    {
        if (!preg_match('/^TKT-[A-Z0-9]{8}$/', $code)) {
            throw new \InvalidArgumentException(
                'Ticket code must match pattern TKT-XXXXXXXX (8 uppercase alphanumeric characters).'
            );
        }

        $this->code = $code;
    }

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toValue(): string
    {
        return $this->code;
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
