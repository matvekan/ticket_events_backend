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
            id: $venue->id()->toString(),
            name: (string) $venue->name(),
            address: (string) $venue->address(),
            city: (string) $venue->city(),
            latitude: $venue->latitude(),
            longitude: $venue->longitude(),
        );
    }

    /**
     * @param array<int, Venue> $venues
     * @return array<int, VenueDto>
     */
    public function fromVenueList(array $venues): array
    {
        return array_map(fn (Venue $venue): VenueDto => $this->fromVenue($venue), $venues);
    }
}
