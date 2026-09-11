<?php

declare(strict_types=1);

namespace App\Application\Dto;

final readonly class PriceRangeDto
{
    public function __construct(
        public int $min,
        public int $max,
        public string $currency,
    ) {
    }
}
