<?php

declare(strict_types=1);

namespace App\Application\Dto;

final class TicketDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $eventSeatId,
        public readonly string $eventTitle,
        public readonly string $eventDate,
        public readonly string $venueName,
        public readonly int $priceAmount,
    ) {
    }
}
