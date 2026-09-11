<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Venue;
use App\Domain\ValueObject\VenueId;

interface VenueRepositoryInterface
{
    public function findById(VenueId $id): ?Venue;

    public function findAll(): array;

    public function save(Venue $venue): void;
}
