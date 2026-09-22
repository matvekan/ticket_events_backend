<?php

declare(strict_types=1);

namespace App\Domain\Repository;

interface EventSearchInterface
{
    /**
     * @return array<int, \App\Application\Dto\EventDto>
     */
    public function search(
        ?string $query,
        ?string $city,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
        int $limit,
        ?string $cursor,
    ): array;
}
