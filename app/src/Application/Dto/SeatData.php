<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class SeatData
{
    public function __construct(
        public string $row,
        public int $number,
        public string $type,
        public ?string $sector = null,
    ) {
    }
}
