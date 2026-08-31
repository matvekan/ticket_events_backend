<?php

declare(strict_types=1);

namespace App\Application\Dto\Analytics;

final readonly class AnalyticsByDayDto
{
    public function __construct(
        public string $day,
        public int $revenue,
        public int $refunds,
    ) {
    }
}