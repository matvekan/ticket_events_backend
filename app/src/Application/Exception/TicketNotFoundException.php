<?php

declare(strict_types=1);

namespace App\Application\Exception;

final class TicketNotFoundException extends EntityNotFoundException
{
    public function __construct(string $code)
    {
        parent::__construct(sprintf('Ticket with code "%s" not found.', $code));
    }
}
