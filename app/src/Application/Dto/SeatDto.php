<?php

declare(strict_types=1);

namespace App\Application\Dto;

final class SeatDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $row,
        public readonly int $number,
        public readonly ?string $sector,
        public readonly string $type,
        public readonly ?int $priceAmount = null,
        public readonly ?string $status = null,
    ) {
    }
}
