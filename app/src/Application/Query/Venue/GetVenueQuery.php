<?php

declare(strict_types=1);

namespace App\Application\Query\Venue;

use App\Application\Query\QueryInterface;
use Symfony\Component\Uid\Uuid;

final class GetVenueQuery implements QueryInterface
{
    public function __construct(
        public readonly Uuid $venueId,
    ) {
    }
}
