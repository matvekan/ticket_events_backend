<?php

declare(strict_types=1);

namespace App\Application\Dto;

/**
 * Raw verification projection for a ticket code.
 * The decision logic (valid / invalid + reason) lives in the query handler.
 */
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
