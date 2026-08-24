<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Ticket;
use App\Domain\ValueObject\OrderId;

interface TicketRepositoryInterface
{
    public function findByCode(string $code): ?Ticket;

    /** @return Ticket[] */
    public function findByOrderId(OrderId $orderId): array;
}
