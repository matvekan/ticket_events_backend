<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Domain\Event\OrderRefundedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class InvalidateSeatCacheOnOrderRefunded
{
    public function __construct(
        private readonly SeatCacheInvalidator $invalidator,
    ) {
    }

    public function __invoke(OrderRefundedEvent $event): void
    {
        $this->invalidator->invalidateForSeats($event->getEventSeatIds());
    }
}
