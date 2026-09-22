<?php

declare(strict_types=1);

namespace App\Domain\Service\Order;

use App\Domain\ValueObject\TicketCode;

final readonly class TicketCodeGenerator
{
    public function generate(): TicketCode
    {
        return new TicketCode(\sprintf('TKT-%s', strtoupper(substr(bin2hex(random_bytes(4)), 0, 8))));
    }
}
