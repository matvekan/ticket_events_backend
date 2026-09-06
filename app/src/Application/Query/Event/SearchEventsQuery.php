<?php

declare(strict_types=1);

namespace App\Application\Query\Event;

use App\Application\Query\QueryInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class SearchEventsQuery implements QueryInterface
{
    public function __construct(
        #[Assert\Length(min: 2, max: 255)]
        public ?string $query = null,

        #[Assert\Length(min: 2, max: 100)]
        public ?string $city = null,

        #[Assert\Type(\DateTimeImmutable::class)]
        public ?\DateTimeImmutable $dateFrom = null,

        #[Assert\Type(\DateTimeImmutable::class)]
        public ?\DateTimeImmutable $dateTo = null,

        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Range(min: 1, max: 100)]
        public int $limit = 20,
    ) {
    }
}
