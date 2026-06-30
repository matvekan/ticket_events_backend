<?php

declare(strict_types=1);

namespace App\Domain\Event;

use Symfony\Component\Uid\Uuid;

final class OrderCancelledEvent
{
    /** @param Uuid[] $eventSeatIds */
    public function __construct(
        private readonly Uuid $orderId,
        private readonly Uuid $userId,
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

    /** @return Uuid[] */
    public function getEventSeatIds(): array
    {
        return $this->eventSeatIds;
    }
}
