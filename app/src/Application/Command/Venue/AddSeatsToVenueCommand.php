<?php

declare(strict_types=1);

namespace App\Application\Command\Venue;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;

final class AddSeatsToVenueCommand implements CommandInterface
{
    /** @param array<int, array{row: string, number: int, type: string, sector?: string}> $seats */
    public function __construct(
        public readonly Uuid $venueId,
        public readonly array $seats,
    ) {
    }
}
