<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\EventSeat;
use Symfony\Component\Uid\Uuid;

interface EventSeatRepositoryInterface
{
    public function findById(Uuid $id): ?EventSeat;

    /** @param Uuid[] $ids @return EventSeat[] */
    public function lockAndFindByIds(array $ids): array;

    /** @return EventSeat[] */
    public function findAvailableByEventId(Uuid $eventId): array;
}
