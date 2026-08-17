<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class EventDto
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $date,
        public string $venueName,
        public string $venueCity,
        public int $priceMin,
        public int $priceMax,
        public string $status,
    ) {
    }
}
