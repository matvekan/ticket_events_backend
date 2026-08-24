<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Seat;
use App\Domain\ValueObject\SeatId;
use App\Domain\ValueObject\VenueId;

interface SeatRepositoryInterface
{
    public function findById(SeatId $id): ?Seat;

    /** @return Seat[] */
    public function findByVenueId(VenueId $venueId): array;

    /** @param Seat[] $seats */
    public function saveAll(array $seats): void;
}
