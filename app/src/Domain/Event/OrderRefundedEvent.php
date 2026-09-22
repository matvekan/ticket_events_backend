<?php

declare(strict_types=1);

namespace App\Domain\Event;

final class OrderRefundedEvent
{
    /**
     * @param array<int, string> $eventSeatIds
     */
    public function __construct(
        private readonly string $orderId,
        private readonly string $userId,
        private readonly int $totalAmount,
        private readonly array $eventSeatIds,
    ) {
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function totalAmount(): int
    {
        return $this->totalAmount;
    }

    /**
     * @return array<int, string>
     */
    public function eventSeatIds(): array
    {
        return $this->eventSeatIds;
    }
}
