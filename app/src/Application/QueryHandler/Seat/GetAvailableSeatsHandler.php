<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Seat;

use App\Application\Cache\SeatAvailabilityCacheInterface;
use App\Application\Port\SeatAvailabilityReadRepositoryInterface;
use App\Application\Query\QueryHandlerInterface;
use App\Application\Query\Seat\GetAvailableSeatsQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetAvailableSeatsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly SeatAvailabilityReadRepositoryInterface $seatAvailability,
        private readonly SeatAvailabilityCacheInterface $seatAvailabilityCache,
    ) {
    }

    /** @return \App\Application\Dto\SeatDto[] */
    public function __invoke(GetAvailableSeatsQuery $query): array
    {
        $cached = $this->seatAvailabilityCache->getAvailable($query->eventId);
        if ($cached !== null) {
            return $cached;
        }

        $seats = $this->seatAvailability->findAvailableByEventId($query->eventId);
        $this->seatAvailabilityCache->setAvailable($query->eventId, $seats);

        return $seats;
    }
}
