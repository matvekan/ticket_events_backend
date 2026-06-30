<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use App\Domain\Event\OrderPaidEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class LogPayment
{
    public function __construct(
        private readonly EventLogger $logger,
    ) {
    }

    public function __invoke(OrderPaidEvent $event): void
    {
        $this->logger->logPayment(
            (string) $event->getOrderId(),
            (string) $event->getUserId(),
            $event->getTotalAmount(),
        );
    }
}
