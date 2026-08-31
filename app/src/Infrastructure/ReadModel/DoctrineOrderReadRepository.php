<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel;

use App\Application\Dto\OrderDto;
use App\Application\Dto\TicketDto;
use App\Application\Port\OrderReadRepositoryInterface;
use Doctrine\DBAL\Connection;

final class DoctrineOrderReadRepository implements OrderReadRepositoryInterface
{
    private const ORDER_SELECT = <<<'SQL'
        SELECT o.id, o.status, o.total_amount, o.total_currency, o.created_at,
               t.id AS ticket_id, t.code AS ticket_code, t.status AS ticket_status,
               t.event_seat_id, t.price_amount, t.price_currency,
               e.title AS event_title, e.date AS event_date, v.name AS venue_name
        FROM orders o
        LEFT JOIN tickets t ON t.order_id = o.id
        LEFT JOIN event_seats es ON es.id = t.event_seat_id
        LEFT JOIN events e ON e.id = es.event_id
        LEFT JOIN venues v ON v.id = e.venue_id
    SQL;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findByIdAndUser(string $orderId, ?string $userId): ?OrderDto
    {
        $sql = self::ORDER_SELECT . ' WHERE o.id = :orderId';
        $params = ['orderId' => $orderId];

        if ($userId !== null) {
            $sql .= ' AND o.user_id = :userId';
            $params['userId'] = $userId;
        }

        $rows = $this->connection->fetchAllAssociative($sql, $params);

        if ($rows === []) {
            return null;
        }

        return $this->hydrate($rows);
    }

    public function findByUserId(string $userId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            self::ORDER_SELECT . ' WHERE o.user_id = :userId ORDER BY o.created_at DESC',
            ['userId' => $userId],
        );

        if ($rows === []) {
            return [];
        }

        $ordersById = [];
        foreach ($rows as $row) {
            $ordersById[$row['id']][] = $row;
        }

        return array_map([$this, 'hydrate'], array_values($ordersById));
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function hydrate(array $rows): OrderDto
    {
        $first = $rows[0];

        return new OrderDto(
            id: (string) $first['id'],
            status: (string) $first['status'],
            total: (int) $first['total_amount'],
            totalCurrency: (string) $first['total_currency'],
            createdAt: $this->formatDate($first['created_at']),
            tickets: array_values(array_filter(array_map(
                fn (array $row): ?TicketDto => $row['ticket_id'] !== null ? $this->hydrateTicket($row) : null,
                $rows,
            ))),
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrateTicket(array $row): TicketDto
    {
        return new TicketDto(
            id: (string) $row['ticket_id'],
            code: (string) $row['ticket_code'],
            eventSeatId: (string) $row['event_seat_id'],
            eventTitle: $row['event_title'] !== null ? (string) $row['event_title'] : '',
            eventDate: isset($row['event_date']) && $row['event_date'] !== null
                ? $this->formatDate($row['event_date'])
                : '',
            venueName: $row['venue_name'] !== null ? (string) $row['venue_name'] : '',
            priceAmount: (int) $row['price_amount'],
            priceCurrency: (string) $row['price_currency'],
        );
    }

    private function formatDate(mixed $value): string
    {
        return $value instanceof \DateTimeImmutable
            ? $value->format('c')
            : (new \DateTimeImmutable((string) $value))->format('c');
    }
}
