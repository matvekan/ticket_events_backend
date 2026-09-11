<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Shared\StringValueObjectInterface;

final readonly class MessageText implements StringValueObjectInterface
{
    public const MAX_LENGTH = 2000;

    private string $value;

    public function __construct(string $text)
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            throw new BusinessRuleViolationException('Message text cannot be empty.');
        }
        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new BusinessRuleViolationException(sprintf('Message text is too long (max %d characters).', self::MAX_LENGTH));
        }
        $this->value = $trimmed;
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
        return $this->value;
    }

    public function toValue(): string
    {
        return $this->value;
    }

    public function equals(StringValueObjectInterface $other): bool
    {
        if (! $other instanceof self) {
            return false;
        }

        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
