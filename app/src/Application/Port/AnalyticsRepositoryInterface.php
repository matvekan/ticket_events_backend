<?php

declare(strict_types=1);

namespace App\Application\Port;

use App\Application\Dto\Analytics\AnalyticsTotalsDto;
use App\Application\Dto\Analytics\TableTotalsDto;

interface AnalyticsRepositoryInterface
{
    public function tableTotals(string $table): TableTotalsDto;

    public function countRows(string $table): int;

    public function byDay(): array;

    public function topUsers(): array;

    public function recentPayments(): array;

    public function totals(): AnalyticsTotalsDto;
}
