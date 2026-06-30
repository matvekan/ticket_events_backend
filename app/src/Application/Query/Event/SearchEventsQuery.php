<?php

declare(strict_types=1);

namespace App\Application\Query\Event;

use App\Application\Query\QueryInterface;

final class SearchEventsQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $query = null,
        public readonly ?string $city = null,
        public readonly ?\DateTimeImmutable $dateFrom = null,
        public readonly ?\DateTimeImmutable $dateTo = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {
    }
}
