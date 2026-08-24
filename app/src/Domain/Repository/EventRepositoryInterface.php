<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Event;
use App\Domain\ValueObject\EventId;

interface EventRepositoryInterface
{
    public function findById(EventId $id): ?Event;

    /** @return Event[] */
    public function findPublished(int $limit, int $offset): array;

    /**
     * @return Event[]
     */
    public function searchPublished(
        ?string $query,
        ?string $city,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
        int $limit,
        int $offset,
    ): array;

    /** @return Event[] */
    public function findAll(): array;

    public function save(Event $event): void;
}
