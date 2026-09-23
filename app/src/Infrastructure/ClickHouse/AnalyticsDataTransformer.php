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
            $day = $row['day'] ?? '';
            $revenue = $row['revenue'] ?? 0;
            $refunds = $row['refunds'] ?? 0;
            assert(is_scalar($day));
            assert(is_scalar($revenue));
            assert(is_scalar($refunds));

            $result[] = new AnalyticsByDayDto(
                day: (string) $day,
                revenue: (int) $revenue,
                refunds: (int) $refunds,
            );
        }

        return $result;
    }
}
