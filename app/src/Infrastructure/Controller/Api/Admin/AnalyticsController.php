<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller\Api\Admin;

use ClickHouseDB\Client as ClickHouseClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin/analytics', name: 'admin.analytics', methods: ['GET'])]
final class AnalyticsController
{
    private const TABLES = [
        'seat_reservations',
        'order_payments',
        'order_cancellations',
        'order_refunds',
    ];

    public function __construct(
        private readonly ClickHouseClient $clickhouse,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        try {
            $totals = [
                'payments' => $this->tableTotals('order_payments'),
                'refunds' => $this->tableTotals('order_refunds'),
                'cancellations' => $this->countRows('order_cancellations'),
                'reservations' => $this->countRows('seat_reservations'),
            ];

            return new JsonResponse([
                'totals' => $totals,
                'byDay' => $this->byDay(),
                'topUsers' => $this->topUsers(),
                'recentPayments' => $this->recentPayments(),
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse(
                ['message' => 'Analytics unavailable: ' . $exception->getMessage()],
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }
    }

    private function database(): string
    {
        return $this->clickhouse->settings['database'] ?? 'default';
    }

    private function table(string $name): string
    {
        return sprintf('%s.%s', $this->database(), $name);
    }

    /**
     * @return array{count: int, amount: int}
     */
    private function tableTotals(string $table): array
    {
        $row = $this->select("SELECT count() AS cnt, coalesce(sum(amount), 0) AS total FROM {$this->table($table)}");

        return [
            'count' => (int) ($row[0]['cnt'] ?? 0),
            'amount' => (int) ($row[0]['total'] ?? 0),
        ];
    }

    private function countRows(string $table): int
    {
        $row = $this->select("SELECT count() AS cnt FROM {$this->table($table)}");

        return (int) ($row[0]['cnt'] ?? 0);
    }

    /**
     * @return array<int, array{day: string, revenue: int, refunds: int}>
     */
    private function byDay(): array
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
            $byDay[(string) $row['day']] = [
                'day' => (string) $row['day'],
                'revenue' => (int) $row['revenue'],
                'refunds' => 0,
            ];
        }
        foreach ($refunds as $row) {
            $day = (string) $row['day'];
            if (!isset($byDay[$day])) {
                $byDay[$day] = ['day' => $day, 'revenue' => 0, 'refunds' => 0];
            }
            $byDay[$day]['refunds'] = (int) $row['refunds'];
        }

        return array_values($byDay);
    }

    /**
     * @return array<int, array{user_id: string, orders: int, revenue: int}>
     */
    private function topUsers(): array
    {
        $rows = $this->select(
            "SELECT user_id, count() AS orders, sum(amount) AS revenue
             FROM {$this->table('order_payments')}
             GROUP BY user_id ORDER BY revenue DESC LIMIT 10",
        );

        return array_map(static fn (array $row): array => [
            'user_id' => (string) $row['user_id'],
            'orders' => (int) $row['orders'],
            'revenue' => (int) $row['revenue'],
        ], $rows);
    }

    /**
     * @return array<int, array{order_id: string, user_id: string, amount: int, timestamp: string}>
     */
    private function recentPayments(): array
    {
        $rows = $this->select(
            "SELECT order_id, user_id, amount, timestamp
             FROM {$this->table('order_payments')}
             ORDER BY timestamp DESC LIMIT 10",
        );

        return array_map(static fn (array $row): array => [
            'order_id' => (string) $row['order_id'],
            'user_id' => (string) $row['user_id'],
            'amount' => (int) $row['amount'],
            'timestamp' => (string) $row['timestamp'],
        ], $rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function select(string $sql): array
    {
        $result = $this->clickhouse->select($sql);

        return $result->rows();
    }
}
