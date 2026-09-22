<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use App\Application\Dto\Analytics\AnalyticsByDayDto;

final class AnalyticsDataTransformer
{
    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, AnalyticsByDayDto>
     */
    public function transformByDay(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $result[] = new AnalyticsByDayDto(
                day: (string) $row['day'],
                revenue: (int) $row['revenue'],
                refunds: (int) $row['refunds'],
            );
        }

        return $result;
    }
}
