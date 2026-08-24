<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Dto\EventDto;

interface EventReadRepositoryInterface
{
    /** @return EventDto[] */
    public function findPublished(int $limit, int $offset): array;

    /**
     * @return EventDto[]
     */
    public function searchPublished(
        ?string $query,
        ?string $city,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
        int $limit,
        int $offset,
    ): array;

    public function findById(string $id): ?EventDto;
}
