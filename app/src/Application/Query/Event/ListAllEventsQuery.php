<?php

declare(strict_types=1);

namespace App\Application\Query\Event;

use App\Application\Query\QueryInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class ListAllEventsQuery implements QueryInterface
{
    public function __construct(
        public ?string $cursor = null,
        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 20,
    ) {
    }
}
