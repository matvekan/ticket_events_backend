<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Dto\EventDto;

interface EventSearchInterface
{
    /**
     * @return EventDto[]
     */
    public function search(
        ?string $query,
        ?string $city,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
        int $limit,
        int $offset,
    ): array;
}
