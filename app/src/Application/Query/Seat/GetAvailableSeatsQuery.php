<?php

declare(strict_types=1);

namespace App\Application\Query\Seat;

use App\Application\Query\QueryInterface;
use Symfony\Component\Uid\Uuid;

final class GetAvailableSeatsQuery implements QueryInterface
{
    public function __construct(
        public readonly Uuid $eventId,
    ) {
    }
}
