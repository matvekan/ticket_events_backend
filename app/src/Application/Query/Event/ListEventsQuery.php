<?php

declare(strict_types=1);

namespace App\Application\Query\Event;

use App\Application\Query\QueryInterface;

final class ListEventsQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 20,
    ) {
    }
}
