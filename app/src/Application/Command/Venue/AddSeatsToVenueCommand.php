<?php

declare(strict_types=1);

namespace App\Application\Command\Venue;

use App\Application\Command\CommandInterface;
use App\Application\Dto\SeatData;
use Symfony\Component\Validator\Constraints as Assert;

final class AddSeatsToVenueCommand implements CommandInterface
{
    /** @param SeatData[] $seats */
    public function __construct(
        #[Assert\Uuid]
        public readonly string $venueId,

        #[Assert\NotBlank]
        #[Assert\Count(min: 1)]
        #[Assert\All([
            new Assert\Type(SeatData::class),
        ])]
        public readonly array $seats,
    ) {
    }
}
