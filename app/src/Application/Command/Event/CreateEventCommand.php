<?php

declare(strict_types=1);

namespace App\Application\Command\Event;

use App\Application\Command\CommandInterface;
use Symfony\Component\Uid\Uuid;

final class CreateEventCommand implements CommandInterface
{
    /** @param array<int, array{seatId: Uuid, priceAmount: int}> $seats */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly \DateTimeImmutable $date,
        public readonly Uuid $venueId,
        public readonly array $seats,
    ) {
    }
}
