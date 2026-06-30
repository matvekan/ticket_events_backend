<?php

declare(strict_types=1);

namespace App\Application\Dto\Factory;

use App\Application\Dto\VenueDto;
use App\Domain\Entity\Venue;

final class VenueDtoFactory
{
    public function fromVenue(Venue $venue): VenueDto
    {
        return new VenueDto(
            id: $venue->getId()->toRfc4122(),
            name: $venue->getName(),
            address: $venue->getAddress(),
            city: $venue->getCity(),
        );
    }
}
