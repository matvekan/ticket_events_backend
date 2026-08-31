<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel;

use App\Application\Dto\TicketVerificationData;
use App\Application\Port\TicketVerificationReadRepositoryInterface;
use Doctrine\DBAL\Connection;

final class DoctrineTicketVerificationRepository implements TicketVerificationReadRepositoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findByCode(string $code): ?TicketVerificationData
    {
        $row = $this->connection->fetchAssociative(
            <<<'SQL'
                SELECT t.code,
                       o.status AS order_status,
                       e.status AS event_status,
                       e.title  AS event_title,
                       e.date   AS event_date,
                       v.name   AS venue_name,
                       s.row    AS seat_row,
                       s.number AS seat_number
                FROM tickets t
                JOIN orders o ON o.id = t.order_id
                JOIN event_seats es ON es.id = t.event_seat_id
                JOIN events e ON e.id = es.event_id
                JOIN venues v ON v.id = e.venue_id
                JOIN seats s ON s.id = es.seat_id
                WHERE t.code = :code
                LIMIT 1
            SQL,
            ['code' => $code],
        );

        if ($row === false) {
            return null;
        }

        $eventDate = $row['event_date'] instanceof \DateTimeImmutable
            ? $row['event_date']
            : new \DateTimeImmutable((string) $row['event_date']);

        return new TicketVerificationData(
            code: (string) $row['code'],
            orderStatus: (string) $row['order_status'],
            eventStatus: (string) $row['event_status'],
            eventTitle: (string) $row['event_title'],
            eventDate: $eventDate->format('Y-m-d H:i:s'),
            venueName: (string) $row['venue_name'],
            seatRow: (string) $row['seat_row'],
            seatNumber: (int) $row['seat_number'],
        );
    }
}
