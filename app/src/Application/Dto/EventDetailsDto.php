<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class EventDetailsDto
{
    /** @param SeatDto[] $availableSeats */
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
        public float $priceMin,
        public float $priceMax,
        public string $status,
        public array $availableSeats,
    ) {
    }
}
