<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Seat;

use App\Application\Cache\SeatAvailabilityCacheInterface;
use App\Application\Dto\Factory\SeatDtoFactory;
use App\Application\Dto\SeatDto;
use App\Application\Query\QueryHandlerInterface;
use App\Application\Query\Seat\GetAvailableSeatsQuery;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\ValueObject\EventId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetAvailableSeatsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EventSeatRepositoryInterface $eventSeats,
        private readonly SeatDtoFactory $seatDtoFactory,
        private readonly SeatAvailabilityCacheInterface $seatAvailabilityCache,
    ) {
    }

    /** @return SeatDto[] */
    public function __invoke(GetAvailableSeatsQuery $query): array
    {
        $cached = $this->seatAvailabilityCache->getAvailable($query->eventId);
        if ($cached !== null) {
            return $cached;
        }

        $eventId = new EventId($query->eventId->toRfc4122());
        $eventSeats = $this->eventSeats->findAvailableByEventId($eventId);
        $seats = $this->seatDtoFactory->fromAvailableSeats($eventSeats);
        $this->seatAvailabilityCache->setAvailable($query->eventId, $seats);

        return $seats;
    }
}
