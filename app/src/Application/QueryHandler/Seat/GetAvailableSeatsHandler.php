<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Seat;

use App\Application\Dto\Factory\SeatDtoFactory;
use App\Application\Dto\SeatDto;
use App\Application\Query\QueryHandlerInterface;
use App\Application\Query\Seat\GetAvailableSeatsQuery;
use App\Domain\Repository\EventSeatRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetAvailableSeatsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EventSeatRepositoryInterface $eventSeatRepo,
        private readonly SeatDtoFactory $seatDtoFactory,
    ) {
    }

    /** @return SeatDto[] */
    public function __invoke(GetAvailableSeatsQuery $query): array
    {
        $eventSeats = $this->eventSeatRepo->findAvailableByEventId($query->eventId);

        return $this->seatDtoFactory->fromAvailableSeats($eventSeats);
    }
}
