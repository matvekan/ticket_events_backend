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

    public function findByIds(array $ids): array;

    public function findByVenueId(VenueId $venueId): array;

    public function findAvailableByEventId(EventId $eventId): array;

    public function saveAll(array $seats): void;
}
