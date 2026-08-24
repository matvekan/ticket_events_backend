<?php

declare(strict_types=1);

namespace App\Domain\Event;

final class OrderCancelledEvent
{
    /** @param string[] $eventSeatIds */
    public function __construct(
        private readonly string $orderId,
        private readonly string $userId,
        private readonly array $eventSeatIds,
    ) {
    }

    public function orderId(): string { return $this->orderId; }
    public function userId(): string { return $this->userId; }
    /** @return string[] */
    public function eventSeatIds(): array { return $this->eventSeatIds; }

    /** @deprecated */
    public function getOrderId(): string { return $this->orderId(); }
    /** @deprecated */
    public function getUserId(): string { return $this->userId(); }
    /** @deprecated */
    public function getEventSeatIds(): array { return $this->eventSeatIds(); }
}
