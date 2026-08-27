<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class EntityNotFoundException extends DomainException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
