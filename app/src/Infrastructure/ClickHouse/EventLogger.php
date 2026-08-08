<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use ClickHouseDB\Client as ClickHouseClient;

final class EventLogger
{
    private const TABLE_SCHEMA = <<<'SQL'
CREATE TABLE IF NOT EXISTS {database}.{table} (
    order_id String,
    user_id String,
    amount Float64,
    timestamp DateTime
) ENGINE = MergeTree()
ORDER BY timestamp
SQL;

    private array $initialized = [];

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

    public function ensureTables(): void
    {
        foreach (['seat_reservations', 'order_payments', 'order_cancellations', 'order_refunds'] as $table) {
            $this->ensureTable($table);
        }
    }

    private function write(string $table, string $orderId, string $userId, int $amount): void
    {
        $this->ensureTable($table);

        $this->clickhouse->insert($table, [[
            'order_id' => $orderId,
            'user_id' => $userId,
            'amount' => $amount / 100,
            'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]]);
    }

    private function ensureTable(string $table): void
    {
        if (isset($this->initialized[$table])) {
            return;
        }

        $database = $this->clickhouse->settings['database'] ?? 'default';
        $sql = str_replace(
            ['{database}', '{table}'],
            [$database, $table],
            self::TABLE_SCHEMA,
        );

        $this->clickhouse->write($sql);
        $this->initialized[$table] = true;
    }
}
