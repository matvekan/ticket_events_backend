<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\BusinessRuleViolationException;
use Yokai\DoctrineValueObject\StringValueObject;

final readonly class MessageText implements StringValueObject
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

    public static function fromValue(string $value): static
    {
        return new self($value);
    }

    public function toValue(): string
    {
        return $this->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
