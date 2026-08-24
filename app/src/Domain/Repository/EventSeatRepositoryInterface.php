<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\EventSeat;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventSeatId;

interface EventSeatRepositoryInterface
{
    public function findById(EventSeatId $id): ?EventSeat;

    /** @param EventSeatId[] $ids @return EventSeat[] */
    public function lockAndFindByIds(array $ids): array;

    /** @return EventSeat[] */
    public function findAvailableByEventId(EventId $eventId): array;
}
