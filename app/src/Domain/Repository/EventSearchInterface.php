<?php

declare(strict_types=1);

namespace App\Domain\Repository;


interface EventSearchInterface
{
    public function search(
        ?string $query,
        ?string $city,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
        int $limit,
        int $offset,
    ): array;
}
