<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

enum TicketStatus: string
{
    case Reserved = 'reserved';
    case Active = 'active';
    case Used = 'used';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
