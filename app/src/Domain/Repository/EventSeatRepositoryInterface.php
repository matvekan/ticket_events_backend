<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\EventSeat;
use Symfony\Component\Uid\Uuid;

interface EventSeatRepositoryInterface
{
    public function findById(Uuid $id): ?EventSeat;

    /** @return EventSeat[] */
    public function findByEventId(Uuid $eventId): array;

    /** @param Uuid[] $ids @return EventSeat[] */
    public function lockAndFindByIds(array $ids): array;

    /** @return EventSeat[] */
    public function findAvailableByEventId(Uuid $eventId): array;

    public function save(EventSeat $eventSeat): void;
}
