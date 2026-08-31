<?php

declare(strict_types=1);

namespace App\Application\Dto\Analytics;

final readonly class AnalyticsTotalsDto
{
    public function __construct(
        public TableTotalsDto $payments,
        public TableTotalsDto $refunds,
        public int $cancellations,
        public int $reservations,
    ) {
    }
}