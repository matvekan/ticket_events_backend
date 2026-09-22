<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\EventSeat;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventSeatId;

interface EventSeatRepositoryInterface
{
    public function findById(EventSeatId $id): ?EventSeat;

    /**
     * @param array<int, \App\Domain\ValueObject\EventSeatId> $ids
     * @return array<int, \App\Domain\Entity\EventSeat>
     */
    public function findByIds(array $ids): array;

    /**
     * @param array<int, \App\Domain\ValueObject\EventSeatId> $ids
     * @return array<int, \App\Domain\Entity\EventSeat>
     */
    public function lockAndFindByIds(array $ids): array;

    /**
     * @return array<int, EventSeat>
     */
    public function findAvailableByEventId(EventId $eventId): array;

    /**
     * @return array<int, EventSeat>
     */
    public function findByEventId(EventId $eventId): array;

    public function save(EventSeat $eventSeat): void;
}
