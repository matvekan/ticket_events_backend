<?php

declare(strict_types=1);

namespace App\Application\Dto;

final class EventDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly string $description,
        public readonly string $date,
        public readonly string $venueName,
        public readonly string $venueCity,
        public readonly float $priceMin,
        public readonly float $priceMax,
        public readonly string $status,
    ) {
    }
}
