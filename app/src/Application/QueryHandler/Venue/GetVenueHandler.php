<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Venue;

use App\Application\Dto\Factory\VenueDtoFactory;
use App\Application\Dto\VenueDto;
use App\Application\Query\QueryHandlerInterface;
use App\Application\Query\Venue\GetVenueQuery;
use App\Domain\Repository\VenueRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetVenueHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly VenueRepositoryInterface $venueRepo,
        private readonly VenueDtoFactory $venueDtoFactory,
    ) {
    }

    public function __invoke(GetVenueQuery $query): ?VenueDto
    {
        $venue = $this->venueRepo->findById($query->venueId);
        if (!$venue) {
            return null;
        }

        return $this->venueDtoFactory->fromVenue($venue);
    }
}
