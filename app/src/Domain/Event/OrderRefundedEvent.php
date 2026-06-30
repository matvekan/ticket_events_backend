<?php

declare(strict_types=1);

namespace App\Domain\Event;

use Symfony\Component\Uid\Uuid;

final class OrderRefundedEvent
{
    /** @param Uuid[] $eventSeatIds */
    public function __construct(
        private readonly Uuid $orderId,
        private readonly Uuid $userId,
        private readonly int $totalAmount,
        private readonly array $eventSeatIds,
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
    public function getEventSeatIds(): array
    {
        return $this->eventSeatIds;
    }
}
