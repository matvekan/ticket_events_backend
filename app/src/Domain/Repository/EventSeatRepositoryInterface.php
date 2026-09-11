<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\EventSeat;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventSeatId;

interface EventSeatRepositoryInterface
{
    public function findById(EventSeatId $id): ?EventSeat;

    public function findByIds(array $ids): array;

    public function lockAndFindByIds(array $ids): array;

    public function findAvailableByEventId(EventId $eventId): array;

    public function findByEventId(EventId $eventId): array;

    public function save(EventSeat $eventSeat): void;
}
