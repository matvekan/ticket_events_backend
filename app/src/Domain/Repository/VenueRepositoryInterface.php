<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Venue;
use Symfony\Component\Uid\Uuid;

interface VenueRepositoryInterface
{
    public function findById(Uuid $id): ?Venue;

    /** @return Venue[] */
    public function findAll(): array;

    public function save(Venue $venue): void;
}
