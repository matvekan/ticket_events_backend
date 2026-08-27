<?php

declare(strict_types=1);

namespace App\Domain\Event;

final class SeatsReservedEvent
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
}
