<?php

declare(strict_types=1);

namespace App\Application\Query\Seat;

use App\Application\Query\QueryInterface;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetAvailableSeatsQuery implements QueryInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $eventId,
    ) {
    }
}
