<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use App\Domain\Event\SeatsReservedEvent;
use App\Domain\Shared\ClockInterface;
use ClickHouseDB\Client as ClickHouseClient;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'event.bus')]
final class LogSeatReservation
{
    private const TABLE = 'seat_reservations';

    public function __construct(
        private readonly ClickHouseClient $clickhouse,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(SeatsReservedEvent $event): void
    {
        $this->clickhouse->insert(self::TABLE, [[
            'order_id' => (string) $event->orderId(),
            'user_id' => (string) $event->userId(),
            'amount' => 0,
            'timestamp' => $this->clock->now()->format('Y-m-d H:i:s'),
        ]]);
    }
}