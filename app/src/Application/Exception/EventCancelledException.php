<?php

declare(strict_types=1);

namespace App\Application\Exception;

use App\Domain\Exception\BusinessRuleViolationException;

final class EventCancelledException extends BusinessRuleViolationException
{
    public function __construct(string $code)
    {
        parent::__construct(sprintf('Событие по билету "%s" отменено.', $code));
    }
}