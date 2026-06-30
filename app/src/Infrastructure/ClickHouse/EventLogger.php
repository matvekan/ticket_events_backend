<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use ClickHouseDB\Client as ClickHouseClient;

final class EventLogger
{
    public function __construct(
        private readonly ClickHouseClient $clickhouse,
    ) {
    }

    public function logSeatReservation(string $orderId, string $userId): void
    {
        $this->write('seat_reservations', $orderId, $userId, 0);
    }

    public function logPayment(string $orderId, string $userId, int $amount): void
    {
        $this->write('order_payments', $orderId, $userId, $amount);
    }

    public function logCancellation(string $orderId, string $userId): void
    {
        $this->write('order_cancellations', $orderId, $userId, 0);
    }

    public function logRefund(string $orderId, string $userId, int $amount): void
    {
        $this->write('order_refunds', $orderId, $userId, $amount);
    }

    private function write(string $table, string $orderId, string $userId, int $amount): void
    {
        $this->clickhouse->insert($table, [[
            'order_id' => $orderId,
            'user_id' => $userId,
            'amount' => $amount / 100,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]]);
    }
}
