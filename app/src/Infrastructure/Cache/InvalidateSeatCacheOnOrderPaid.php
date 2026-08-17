<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Domain\Event\OrderPaidEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class InvalidateSeatCacheOnOrderPaid
{
    public function __construct(
        private readonly SeatCacheInvalidator $invalidator,
    ) {
    }

    public function __invoke(OrderPaidEvent $event): void
    {
        $this->invalidator->invalidateForOrder($event->getOrderId());
    }
}
