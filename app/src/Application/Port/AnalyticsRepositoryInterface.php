<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Dto\Analytics\AnalyticsTotalsDto;
use App\Application\Dto\Analytics\TableTotalsDto;

interface AnalyticsRepositoryInterface
{
    public function tableTotals(string $table): TableTotalsDto;

    public function countRows(string $table): int;

    /**
     * @return array<int, \App\Application\Dto\Analytics\AnalyticsByDayDto>
     */
    public function byDay(): array;

    /**
     * @return array<int, \App\Application\Dto\Analytics\TopUserDto>
     */
    public function topUsers(): array;

    /**
     * @return array<int, \App\Application\Dto\Analytics\RecentPaymentDto>
     */
    public function recentPayments(): array;

    public function totals(): AnalyticsTotalsDto;
}
