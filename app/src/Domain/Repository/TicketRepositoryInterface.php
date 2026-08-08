<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Ticket;
use Symfony\Component\Uid\Uuid;

interface TicketRepositoryInterface
{
    public function findById(Uuid $id): ?Ticket;

    public function findByCode(string $code): ?Ticket;

    /** @return Ticket[] */
    public function findByOrderId(Uuid $orderId): array;

    public function save(Ticket $ticket): void;
}
