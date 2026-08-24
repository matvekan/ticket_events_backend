<?php

declare(strict_types=1);

namespace App\Application\Port;

interface AnalyticsRepositoryInterface
{
    /** @return array{count: int, amount: int} */
    public function tableTotals(string $table): array;

    public function countRows(string $table): int;

    /** @return array<int, array{day: string, revenue: int, refunds: int}> */
    public function byDay(): array;

    /** @return array<int, array{user_id: string, orders: int, revenue: int}> */
    public function topUsers(): array;

    /** @return array<int, array{order_id: string, user_id: string, amount: int, timestamp: string}> */
    public function recentPayments(): array;

    /** @return array{payments: array{count: int, amount: int}, refunds: array{count: int, amount: int}, cancellations: int, reservations: int} */
    public function totals(): array;
}
