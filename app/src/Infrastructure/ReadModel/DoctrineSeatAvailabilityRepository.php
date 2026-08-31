<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel;

use App\Application\Dto\SeatDto;
use App\Application\Port\SeatAvailabilityReadRepositoryInterface;
use Doctrine\DBAL\Connection;

final class DoctrineSeatAvailabilityRepository implements SeatAvailabilityReadRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findAvailableByEventId(string $eventId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT es.id, s.row, s.number, s.sector, s.type,
                       es.price_amount, es.price_currency, es.status
                FROM event_seats es
                JOIN seats s ON s.id = es.seat_id
                WHERE es.event_id = :eventId AND es.status = 'free'
                ORDER BY s.row, s.number
            SQL,
            ['eventId' => $eventId],
        );

        return array_map(static fn (array $r): SeatDto => new SeatDto(
            id: (string) $r['id'],
            row: (string) $r['row'],
            number: (int) $r['number'],
            sector: $r['sector'] !== null ? (string) $r['sector'] : null,
            type: (string) $r['type'],
            priceAmount: (int) $r['price_amount'],
            priceCurrency: (string) $r['price_currency'],
            status: (string) $r['status'],
        ), $rows);
    }
}
