<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Seat;
use Symfony\Component\Uid\Uuid;

interface SeatRepositoryInterface
{
    public function findById(Uuid $id): ?Seat;

    /** @return Seat[] */
    public function findByVenueId(Uuid $venueId): array;

    public function save(Seat $seat): void;
}
