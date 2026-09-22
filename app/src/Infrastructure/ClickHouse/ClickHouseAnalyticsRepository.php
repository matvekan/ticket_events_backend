<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use App\Application\Dto\Analytics\AnalyticsTotalsDto;
use App\Application\Dto\Analytics\RecentPaymentDto;
use App\Application\Dto\Analytics\TableTotalsDto;
use App\Application\Dto\Analytics\TopUserDto;
use App\Application\Port\AnalyticsRepositoryInterface;
use ClickHouseDB\Client as ClickHouseClient;

final class ClickHouseAnalyticsRepository implements AnalyticsRepositoryInterface
{
    public function __construct(
        private readonly ClickHouseClient $clickhouse,
        private readonly AnalyticsDataTransformer $transformer,
    ) {
    }

    public function totals(): AnalyticsTotalsDto
    {
        $paymentsTable = $this->table('order_payments');
        $refundsTable = $this->table('order_refunds');
        $cancellationsTable = $this->table('order_cancellations');
        $reservationsTable = $this->table('seat_reservations');

        $sql = "
            SELECT
                (SELECT count() FROM (SELECT order_id FROM {$paymentsTable} GROUP BY order_id)) AS payments_count,
                (SELECT coalesce(sum(amt), 0) FROM (SELECT order_id, any(amount) AS amt FROM {$paymentsTable} GROUP BY order_id)) AS payments_amount,
                (SELECT count() FROM (SELECT order_id FROM {$refundsTable} GROUP BY order_id)) AS refunds_count,
                (SELECT coalesce(sum(amt), 0) FROM (SELECT order_id, any(amount) AS amt FROM {$refundsTable} GROUP BY order_id)) AS refunds_amount,
                (SELECT count(DISTINCT order_id) FROM {$cancellationsTable}) AS cancellations_count,
                (SELECT count(DISTINCT order_id) FROM {$reservationsTable}) AS reservations_count
        ";

        $row = $this->select($sql)[0] ?? [];

        return new AnalyticsTotalsDto(
            payments: new TableTotalsDto((int) ($row['payments_count'] ?? 0), (int) ($row['payments_amount'] ?? 0)),
            refunds: new TableTotalsDto((int) ($row['refunds_count'] ?? 0), (int) ($row['refunds_amount'] ?? 0)),
            cancellations: (int) ($row['cancellations_count'] ?? 0),
            reservations: (int) ($row['reservations_count'] ?? 0),
        );
    }

    public function tableTotals(string $table): TableTotalsDto
    {
        $row = $this->select(\sprintf(
            'SELECT count() AS cnt, coalesce(sum(amt), 0) AS total
             FROM (
                 SELECT order_id, any(amount) AS amt
                 FROM %s
                 GROUP BY order_id
             )',
            $this->table($table),
        ));

        return new TableTotalsDto(
            count: (int) ($row[0]['cnt'] ?? 0),
            amount: (int) ($row[0]['total'] ?? 0),
        );
    }

    public function countRows(string $table): int
    {
        $row = $this->select(\sprintf('SELECT count(DISTINCT order_id) AS cnt FROM %s', $this->table($table)));

        return (int) ($row[0]['cnt'] ?? 0);
    }

    /**
     * @return array<int, \App\Application\Dto\Analytics\AnalyticsByDayDto>
     */
    public function byDay(): array
    {
        $paymentsTable = $this->table('order_payments');
        $refundsTable = $this->table('order_refunds');

        $sql = "
            SELECT
                day,
                sum(revenue) AS revenue,
                sum(refunds) AS refunds
            FROM (
                SELECT toDate(ts) AS day, amt AS revenue, 0 AS refunds
                FROM (
                    SELECT order_id, any(timestamp) AS ts, any(amount) AS amt
                    FROM {$paymentsTable}
                    GROUP BY order_id
                )
                WHERE ts >= now() - INTERVAL 14 DAY

                UNION ALL

                SELECT toDate(ts) AS day, 0 AS revenue, amt AS refunds
                FROM (
                    SELECT order_id, any(timestamp) AS ts, any(amount) AS amt
                    FROM {$refundsTable}
                    GROUP BY order_id
                )
                WHERE ts >= now() - INTERVAL 14 DAY
            )
            GROUP BY day
            ORDER BY day
        ";

        $rows = $this->select($sql);

        return $this->transformer->transformByDay($rows);
    }

    /**
     * @return array<int, TopUserDto>
     */
    public function topUsers(): array
    {
        $rows = $this->select(\sprintf(
            'SELECT uid AS user_id, count() AS orders, sum(amt) AS revenue
             FROM (
                 SELECT order_id, any(user_id) AS uid, any(amount) AS amt
                 FROM %s
                 GROUP BY order_id
             )
             GROUP BY user_id ORDER BY revenue DESC LIMIT 10',
            $this->table('order_payments'),
        ));

        return array_map(static fn (array $row): TopUserDto => new TopUserDto(
            userId: (string) $row['user_id'],
            orders: (int) $row['orders'],
            revenue: (int) $row['revenue'],
        ), $rows);
    }

    /**
     * @return array<int, RecentPaymentDto>
     */
    public function recentPayments(): array
    {
        $rows = $this->select(\sprintf(
            'SELECT order_id, any(user_id) AS user_id, any(amount) AS amount, any(timestamp) AS timestamp
             FROM %s
             GROUP BY order_id
             ORDER BY timestamp DESC LIMIT 10',
            $this->table('order_payments'),
        ));

        return array_map(static fn (array $row): RecentPaymentDto => new RecentPaymentDto(
            orderId: (string) $row['order_id'],
            userId: (string) $row['user_id'],
            amount: (int) $row['amount'],
            timestamp: (string) $row['timestamp'],
        ), $rows);
    }

    private function table(string $name): string
    {
        if (!preg_match('/^[a-z_]+$/', $name)) {
            throw new \InvalidArgumentException('Invalid analytics table name.');
        }

        $database = $this->clickhouse->settings()->getDatabase();
        assert(is_string($database));

        return \sprintf('`%s`.`%s`', $database, $name);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function select(string $sql): array
    {
        $result = $this->clickhouse->select($sql)->rows();
        assert(is_array($result));

        return $result;
    }
}
