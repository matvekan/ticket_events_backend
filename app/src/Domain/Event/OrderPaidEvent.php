<?php

declare(strict_types=1);

namespace App\Domain\Event;

final class OrderPaidEvent
{
    /** @param string[] $ticketIds */
    public function __construct(
        private readonly string $orderId,
        private readonly string $userId,
        private readonly int $totalAmount,
        private readonly array $ticketIds,
    ) {
    }

    public function orderId(): string { return $this->orderId; }
    public function userId(): string { return $this->userId; }
    public function totalAmount(): int { return $this->totalAmount; }
    /** @return string[] */
    public function ticketIds(): array { return $this->ticketIds; }
}
