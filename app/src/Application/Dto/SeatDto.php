<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class SeatDto
{
    public function __construct(
        public string $id,
        public string $row,
        public int $number,
        public ?string $sector,
        public string $type,
        public ?int $priceAmount = null,
        public ?string $priceCurrency = null,
        public ?string $status = null,
    ) {
    }
}
