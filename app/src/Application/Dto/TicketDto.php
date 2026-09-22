<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class TicketDto
{
    public function __construct(
        public string $id,
        public string $code,
        public string $eventSeatId,
        public string $eventTitle,
        public string $eventDate,
        public string $venueName,
        public int $priceAmount,
        public string $priceCurrency,
        public string $status,
    ) {
    }
}
