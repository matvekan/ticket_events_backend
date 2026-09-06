<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use App\Application\Dto\Analytics\AnalyticsByDayDto;
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
    ) {
    }

    public function totals(): AnalyticsTotalsDto
    {
        return new AnalyticsTotalsDto(
            payments: $this->tableTotals('order_payments'),
            refunds: $this->tableTotals('order_refunds'),
            cancellations: $this->countRows('order_cancellations'),
            reservations: $this->countRows('seat_reservations'),
        );
    }

    public function tableTotals(string $table): TableTotalsDto
    {
        $row = $this->select(sprintf(
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
        $row = $this->select(sprintf('SELECT count(DISTINCT order_id) AS cnt FROM %s', $this->table($table)));

        return (int) ($row[0]['cnt'] ?? 0);
    }

    public function byDay(): array
    {
        $payments = $this->select(sprintf(
            'SELECT toDate(ts) AS day, sum(amt) AS revenue
             FROM (
                 SELECT order_id, any(timestamp) AS ts, any(amount) AS amt
                 FROM %s
                 GROUP BY order_id
             )
             WHERE ts >= now() - INTERVAL 14 DAY
             GROUP BY day ORDER BY day',
            $this->table('order_payments'),
        ));

        $refunds = $this->select(sprintf(
            'SELECT toDate(ts) AS day, sum(amt) AS refunds
             FROM (
                 SELECT order_id, any(timestamp) AS ts, any(amount) AS amt
                 FROM %s
                 GROUP BY order_id
             )
             WHERE ts >= now() - INTERVAL 14 DAY
             GROUP BY day ORDER BY day',
            $this->table('order_refunds'),
        ));

        $byDay = [];
        foreach ($payments as $row) {
            $byDay[(string) $row['day']] = new AnalyticsByDayDto(
                day: (string) $row['day'],
                revenue: (int) $row['revenue'],
                refunds: 0,
            );
        }
        foreach ($refunds as $row) {
            $day = (string) $row['day'];
            if (!isset($byDay[$day])) {
                $byDay[$day] = new AnalyticsByDayDto(
                    day: $day,
                    revenue: 0,
                    refunds: 0,
                );
            }
            $byDay[$day] = new AnalyticsByDayDto(
                day: $byDay[$day]->day,
                revenue: $byDay[$day]->revenue,
                refunds: (int) $row['refunds'],
            );
        }

        ksort($byDay);

        return array_values($byDay);
    }

    public function topUsers(): array
    {
        $rows = $this->select(sprintf(
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

    public function recentPayments(): array
    {
        $rows = $this->select(sprintf(
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

        return sprintf('`%s`.`%s`', $database, $name);
    }

    private function select(string $sql): array
    {
        return $this->clickhouse->select($sql)->rows();
    }
}
