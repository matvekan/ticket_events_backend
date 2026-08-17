<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Domain\Event\OrderCancelledEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class InvalidateSeatCacheOnOrderCancelled
{
    public function __construct(
        private readonly SeatCacheInvalidator $invalidator,
    ) {
    }

    public function __invoke(OrderCancelledEvent $event): void
    {
        $this->invalidator->invalidateForSeats($event->getEventSeatIds());
    }
}
