<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\BusinessRuleViolationException;

final readonly class MessageText
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

    public function toString(): string { return $this->value; }
    public function __toString(): string { return $this->value; }
}
