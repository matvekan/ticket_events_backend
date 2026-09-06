<?php

declare(strict_types=1);

namespace App\Application\Dto;

readonly class TicketVerificationData
{
    public function __construct(
        public string $code,
        public string $orderStatus,
        public string $eventStatus,
        public string $eventTitle,
        public string $eventDate,
        public string $venueName,
        public string $seatRow,
        public int $seatNumber,
    ) {
    }
}
