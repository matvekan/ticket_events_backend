<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Application\Cache\SeatAvailabilityCacheInterface;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\Repository\TicketRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class SeatCacheInvalidator
{
    public function __construct(
        private readonly EventSeatRepositoryInterface $eventSeats,
        private readonly TicketRepositoryInterface $tickets,
        private readonly SeatAvailabilityCacheInterface $cache,
    ) {
    }

    /** @param Uuid[] $eventSeatIds */
    public function invalidateForSeats(array $eventSeatIds): void
    {
        $eventIds = [];

        foreach ($eventSeatIds as $eventSeatId) {
            $eventSeat = $this->eventSeats->findById($eventSeatId);
            if ($eventSeat === null) {
                continue;
            }

            $eventIds[$eventSeat->event()->id()->toRfc4122()] = $eventSeat->event()->id();
        }

        foreach ($eventIds as $eventId) {
            $this->cache->invalidate($eventId);
        }
    }

    public function invalidateForOrder(Uuid $orderId): void
    {
        $eventIds = [];

        foreach ($this->tickets->findByOrderId($orderId) as $ticket) {
            $eventIds[$ticket->eventSeat()->event()->id()->toRfc4122()] = $ticket->eventSeat()->event()->id();
        }

        foreach ($eventIds as $eventId) {
            $this->cache->invalidate($eventId);
        }
    }
}
