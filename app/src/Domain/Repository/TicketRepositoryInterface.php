<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Application\Dto\TicketVerificationData;
use App\Domain\Entity\Ticket;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\TicketCode;

interface TicketRepositoryInterface
{
    /**
     * @return array<int, \App\Domain\Entity\Ticket>
     */
    public function findByOrderId(OrderId $orderId): array;

    public function findByCode(TicketCode $code): ?Ticket;

    public function findVerificationByCode(string $code): ?TicketVerificationData;

    public function save(Ticket $ticket): void;
}
