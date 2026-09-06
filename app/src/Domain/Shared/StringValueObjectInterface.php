<?php

declare(strict_types=1);

namespace App\Domain\Shared;

interface StringValueObjectInterface
{
    public function toString(): string;

    public function equals(StringValueObjectInterface $other): bool;

    public static function fromValue(string $value): static;

    public static function fromString(string $value): static;

    public function __toString(): string;
}
