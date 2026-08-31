<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Dto\Analytics\AnalyticsByDayDto;
use App\Application\Dto\Analytics\AnalyticsTotalsDto;
use App\Application\Dto\Analytics\RecentPaymentDto;
use App\Application\Dto\Analytics\TableTotalsDto;
use App\Application\Dto\Analytics\TopUserDto;

interface AnalyticsRepositoryInterface
{
    public function tableTotals(string $table): TableTotalsDto;

    public function countRows(string $table): int;

    /** @return AnalyticsByDayDto[] */
    public function byDay(): array;

    /** @return TopUserDto[] */
    public function topUsers(): array;

    /** @return RecentPaymentDto[] */
    public function recentPayments(): array;

    public function totals(): AnalyticsTotalsDto;
}
