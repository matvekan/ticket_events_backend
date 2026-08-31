<?php

declare(strict_types=1);

namespace App\Application\Dto\Analytics;

final readonly class TopUserDto
{
    public function __construct(
        public string $userId,
        public int $orders,
        public int $revenue,
    ) {
    }
}