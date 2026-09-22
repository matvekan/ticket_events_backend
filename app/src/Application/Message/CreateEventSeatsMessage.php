<?php

declare(strict_types=1);

namespace App\Application\Message;

final readonly class CreateEventSeatsMessage
{
    /**
     * @param array<int, array{seatId: string, priceAmount: int}> $seatsData
     */
    public function __construct(
        public string $eventId,
        public string $venueId,
        public array $seatsData,
    ) {
    }
}
