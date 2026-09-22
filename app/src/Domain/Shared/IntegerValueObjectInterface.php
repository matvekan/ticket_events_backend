<?php

declare(strict_types=1);

namespace App\Domain\Shared;

interface IntegerValueObjectInterface
{
    public function toInt(): int;

    public function toValue(): int;

    public function equals(self $other): bool;

    public static function fromValue(int $value): static;

    public function __toString(): string;
}
