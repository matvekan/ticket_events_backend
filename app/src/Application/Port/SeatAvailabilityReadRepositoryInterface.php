<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Dto\SeatDto;

/**
 * Read-side port for the seat map of an event.
 */
interface SeatAvailabilityReadRepositoryInterface
{
    /** @return SeatDto[] */
    public function findAvailableByEventId(string $eventId): array;
}
