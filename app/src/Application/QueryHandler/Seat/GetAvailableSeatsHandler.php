<?php

declare(strict_types=1);

namespace App\Application\QueryHandler\Seat;

use App\Application\Dto\Factory\SeatDtoFactory;
use App\Application\Query\QueryHandlerInterface;
use App\Application\Query\Seat\GetAvailableSeatsQuery;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Shared\CacheInterface;
use App\Domain\ValueObject\EventId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetAvailableSeatsHandler implements QueryHandlerInterface
{
    public function __construct(
        private readonly EventSeatRepositoryInterface $eventSeats,
        private readonly SeatDtoFactory $seatDtoFactory,
        private readonly CacheInterface $cache,
        private readonly int $ttlSeconds,
    ) {
    }

    /**
     * @return array<int, \App\Application\Dto\SeatDto>
     */
    public function __invoke(GetAvailableSeatsQuery $query): array
    {
        $cached = $this->cache->get($query->eventId);
        if ($cached !== null) {
            assert(is_array($cached));

            return $cached;
        }

        $eventSeats = $this->eventSeats->findAvailableByEventId(new EventId($query->eventId));
        $seatDtos = array_map(fn ($es) => $this->seatDtoFactory->fromEventSeat($es), $eventSeats);
        $this->cache->set($query->eventId, $seatDtos, $this->ttlSeconds);

        return $seatDtos;
    }
}
