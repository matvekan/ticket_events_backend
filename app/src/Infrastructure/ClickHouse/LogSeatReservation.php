<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use App\Domain\Event\SeatsReservedEvent;
use ClickHouseDB\Client as ClickHouseClient;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class LogSeatReservation
{
    private const TABLE = 'seat_reservations';

    public function __construct(
        private readonly ClickHouseClient $clickhouse,
    ) {
    }

    public function __invoke(SeatsReservedEvent $event): void
    {
        $this->clickhouse->insert(self::TABLE, [[
            'order_id' => (string) $event->getOrderId(),
            'user_id' => (string) $event->getUserId(),
            'amount' => 0,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]]);
    }
}