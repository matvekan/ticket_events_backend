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
        $row = $this->select("SELECT count() AS cnt, coalesce(sum(amount), 0) AS total FROM {$this->table($table)}");

        return new TableTotalsDto(
            count: (int) ($row[0]['cnt'] ?? 0),
            amount: (int) ($row[0]['total'] ?? 0),
        );
    }

    public function countRows(string $table): int
    {
        $row = $this->select("SELECT count() AS cnt FROM {$this->table($table)}");

        return (int) ($row[0]['cnt'] ?? 0);
    }

    /** @return AnalyticsByDayDto[] */
    public function byDay(): array
    {
        $payments = $this->select(
            "SELECT toDate(timestamp) AS day, sum(amount) AS revenue
             FROM {$this->table('order_payments')}
             WHERE timestamp >= now() - INTERVAL 14 DAY
             GROUP BY day ORDER BY day",
        );
        $refunds = $this->select(
            "SELECT toDate(timestamp) AS day, sum(amount) AS refunds
             FROM {$this->table('order_refunds')}
             WHERE timestamp >= now() - INTERVAL 14 DAY
             GROUP BY day ORDER BY day",
        );

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

        return array_values($byDay);
    }

    /** @return TopUserDto[] */
    public function topUsers(): array
    {
        $rows = $this->select(
            "SELECT user_id, count() AS orders, sum(amount) AS revenue
             FROM {$this->table('order_payments')}
             GROUP BY user_id ORDER BY revenue DESC LIMIT 10",
        );

        return array_map(static fn (array $row): TopUserDto => new TopUserDto(
            userId: (string) $row['user_id'],
            orders: (int) $row['orders'],
            revenue: (int) $row['revenue'],
        ), $rows);
    }

    /** @return RecentPaymentDto[] */
    public function recentPayments(): array
    {
        $rows = $this->select(
            "SELECT order_id, user_id, amount, timestamp
             FROM {$this->table('order_payments')}
             ORDER BY timestamp DESC LIMIT 10",
        );

        return array_map(static fn (array $row): RecentPaymentDto => new RecentPaymentDto(
            orderId: (string) $row['order_id'],
            userId: (string) $row['user_id'],
            amount: (int) $row['amount'],
            timestamp: (string) $row['timestamp'],
        ), $rows);
    }

    private function database(): string
    {
        return $this->clickhouse->settings['database'] ?? 'default';
    }

    private function table(string $name): string
    {
        return sprintf('%s.%s', $this->database(), $name);
    }

    /** @return array<int, array<string, mixed>> */
    private function select(string $sql): array
    {
        $result = $this->clickhouse->select($sql);

        return $result->rows();
    }
}
