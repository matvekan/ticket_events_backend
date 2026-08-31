<?php

declare(strict_types=1);

namespace App\Application\Dto\Analytics;

final readonly class TableTotalsDto
{
    public function __construct(
        public int $count,
        public int $amount,
    ) {
    }
}