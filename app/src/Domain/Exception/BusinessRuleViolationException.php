<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class BusinessRuleViolationException extends DomainException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
