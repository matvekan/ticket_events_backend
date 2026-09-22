<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Seat;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\VenueId;

interface SeatRepositoryInterface
{
    public function findById(SeatId $id): ?Seat;

    /**
     * @param array<int, \App\Domain\ValueObject\SeatId> $ids
     * @return array<int, \App\Domain\Entity\Seat>
     */
    public function findByIds(array $ids): array;

    /**
     * @return array<int, Seat>
     */
    public function findByVenueId(VenueId $venueId): array;

    /**
     * @return array<int, Seat>
     */
    public function findAvailableByEventId(EventId $eventId): array;

    /**
     * @param array<int, \App\Domain\Entity\Seat> $seats
     */
    public function saveAll(array $seats): void;
}
