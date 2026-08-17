<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use App\Domain\Event\OrderRefundedEvent;
use ClickHouseDB\Client as ClickHouseClient;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class LogRefund
{
    private const TABLE = 'order_refunds';

    public function __construct(
        private readonly ClickHouseClient $clickhouse,
    ) {
    }

    public function __invoke(OrderRefundedEvent $event): void
    {
        $this->clickhouse->insert(self::TABLE, [[
            'order_id' => (string) $event->getOrderId(),
            'user_id' => (string) $event->getUserId(),
            'amount' => $event->getTotalAmount(),
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]]);
    }
}