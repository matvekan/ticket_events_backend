<?php

declare(strict_types=1);

namespace App\Domain\Event;

final class OrderPaidEvent
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $userId,
        private readonly int $totalAmount,
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
}
