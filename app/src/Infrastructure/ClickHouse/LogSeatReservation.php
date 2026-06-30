<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use App\Domain\Event\SeatsReservedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class LogSeatReservation
{
    public function __construct(
        private readonly EventLogger $logger,
    ) {
    }

    public function __invoke(SeatsReservedEvent $event): void
    {
        $this->logger->logSeatReservation(
            (string) $event->getOrderId(),
            (string) $event->getUserId(),
        );
    }
}
