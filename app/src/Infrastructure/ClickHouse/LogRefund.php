<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use App\Domain\Event\OrderRefundedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class LogRefund
{
    public function __construct(
        private readonly EventLogger $logger,
    ) {
    }

    public function __invoke(OrderRefundedEvent $event): void
    {
        $this->logger->logRefund(
            (string) $event->getOrderId(),
            (string) $event->getUserId(),
            $event->getTotalAmount(),
        );
    }
}
