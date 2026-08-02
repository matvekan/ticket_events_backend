<?php

declare(strict_types=1);

namespace App\Application\Query\Event;

use App\Application\Query\QueryInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class SearchEventsQuery implements QueryInterface
{
    public function __construct(
        #[Assert\Length(min: 2, max: 255)]
        public readonly ?string $query = null,

        #[Assert\Length(min: 2, max: 100)]
        public readonly ?string $city = null,

        #[Assert\Type(\DateTimeImmutable::class)]
        public readonly ?\DateTimeImmutable $dateFrom = null,

        #[Assert\Type(\DateTimeImmutable::class)]
        public readonly ?\DateTimeImmutable $dateTo = null,

        #[Assert\Positive]
        public readonly int $page = 1,

        #[Assert\Range(min: 1, max: 100)]
        public readonly int $limit = 20,
    ) {
    }
}
