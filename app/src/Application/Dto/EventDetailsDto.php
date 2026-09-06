<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class EventDetailsDto
{
    
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $date,
        public string $venueName,
        public string $venueAddress,
        public string $venueCity,
        public ?float $venueLatitude,
        public ?float $venueLongitude,
        public int $priceMin,
        public int $priceMax,
        public string $priceCurrency,
        public string $status,
        public array $seats,
    ) {
    }
}
