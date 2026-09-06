<?php

declare(strict_types=1);

namespace App\Application\Exception;

use App\Domain\Exception\BusinessRuleViolationException;

final class OrderNotPaidException extends BusinessRuleViolationException
{
    public function __construct(string $code)
    {
        parent::__construct(sprintf('Order for ticket "%s" not paid.', $code));
    }
}