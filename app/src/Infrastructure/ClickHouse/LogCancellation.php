<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use App\Domain\Event\OrderCancelledEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class LogCancellation
{
    public function __construct(
        private readonly EventLogger $logger,
    ) {
    }

    public function __invoke(OrderCancelledEvent $event): void
    {
        $this->logger->logCancellation(
            (string) $event->getOrderId(),
            (string) $event->getUserId(),
        );
    }
}
