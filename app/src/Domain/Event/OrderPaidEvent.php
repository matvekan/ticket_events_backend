<?php

declare(strict_types=1);

namespace App\Domain\Event;

use Symfony\Component\Uid\Uuid;

final class OrderPaidEvent
{
    /** @param Uuid[] $ticketIds */
    public function __construct(
        private readonly Uuid $orderId,
        private readonly Uuid $userId,
        private readonly int $totalAmount,
        private readonly array $ticketIds,
    ) {
    }

    public function getOrderId(): Uuid
    {
        return $this->orderId;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    /** @return Uuid[] */
    public function getTicketIds(): array
    {
        return $this->ticketIds;
    }
}
