<?php

declare(strict_types=1);

namespace App\Application\Query\Event;

use App\Application\Query\QueryInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ListEventsQuery implements QueryInterface
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,

        #[Assert\Range(min: 1, max: 100)]
        public readonly int $limit = 20,
    ) {
    }
}
