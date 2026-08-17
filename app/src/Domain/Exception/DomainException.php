<?php

declare(strict_types=1);

namespace App\Domain\Exception;

class DomainException extends \DomainException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
