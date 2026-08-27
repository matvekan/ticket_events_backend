<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Ticket;
use App\Domain\ValueObject\OrderId;

interface TicketRepositoryInterface
{
    /** @return Ticket[] */
    public function findByOrderId(OrderId $orderId): array;
}
