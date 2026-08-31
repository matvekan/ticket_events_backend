<?php

declare(strict_types=1);

namespace App\Application\Dto\Analytics;

final readonly class RecentPaymentDto
{
    public function __construct(
        public string $orderId,
        public string $userId,
        public int $amount,
        public string $timestamp,
    ) {
    }
}