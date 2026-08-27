<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel;

use App\Application\Dto\EventDto;
use App\Application\Port\EventReadRepositoryInterface;
use Doctrine\DBAL\Connection;

final class DoctrineEventReadRepository implements EventReadRepositoryInterface
{
    /** Price aggregate comes from event_seats so list and search return the same contract as Elasticsearch. */
    private const SELECT = <<<'SQL'
        SELECT e.id, e.title, e.description, e.date,
               v.name AS venue_name, v.city AS venue_city,
               e.status,
               COALESCE(p.price_min, 0) AS price_min,
               COALESCE(p.price_max, 0) AS price_max,
               COALESCE(p.price_currency, 'BYN') AS price_currency
        FROM events e
        JOIN venues v ON v.id = e.venue_id
        LEFT JOIN (
            SELECT event_id,
                   MIN(price_amount)    AS price_min,
                   MAX(price_amount)    AS price_max,
                   MIN(price_currency)  AS price_currency
            FROM event_seats
            GROUP BY event_id
        ) p ON p.event_id = e.id
    SQL;

    public function __construct(private readonly Connection $connection) {}

    /** @return EventDto[] */
    public function findPublished(int $limit, int $offset): array
    {
        $rows = $this->connection->fetchAllAssociative(
            self::SELECT . " WHERE e.status = 'published' ORDER BY e.date DESC LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset],
        );

        return array_map(fn(array $r) => $this->mapRow($r), $rows);
    }

    public function searchPublished(?string $query, ?string $city, ?\DateTimeImmutable $dateFrom, ?\DateTimeImmutable $dateTo, int $limit, int $offset): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('e.id, e.title, e.description, e.date, v.name as venue_name, v.city as venue_city, e.status,
                      COALESCE(MIN(es.price_amount), 0) AS price_min,
                      COALESCE(MAX(es.price_amount), 0) AS price_max,
                      COALESCE(MIN(es.price_currency), :defaultCurrency) AS price_currency')
            ->from('events', 'e')
            ->join('e', 'venues', 'v', 'v.id = e.venue_id')
            ->leftJoin('e', 'event_seats', 'es', 'es.event_id = e.id')
            ->where('e.status = :status')
            ->setParameter('status', 'published')
            ->setParameter('defaultCurrency', 'BYN')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('e.date', 'DESC')
            ->groupBy('e.id, e.title, e.description, e.date, v.name, v.city, e.status');

        if ($query !== null && $query !== '') {
            $qb->andWhere('(e.title LIKE :q OR e.description LIKE :q)')->setParameter('q', '%' . $query . '%');
        }
        if ($city !== null && $city !== '') {
            $qb->andWhere('v.city LIKE :city')->setParameter('city', '%' . $city . '%');
        }
        if ($dateFrom !== null) {
            $qb->andWhere('e.date >= :df')->setParameter('df', $dateFrom->format('Y-m-d H:i:s'));
        }
        if ($dateTo !== null) {
            $qb->andWhere('e.date <= :dt')->setParameter('dt', $dateTo->format('Y-m-d H:i:s'));
        }

        $rows = $qb->executeQuery()->fetchAllAssociative();

        return array_map(fn(array $r) => $this->mapRow($r), $rows);
    }

    public function findById(string $id): ?EventDto
    {
        $row = $this->connection->fetchAssociative(
            self::SELECT . ' WHERE e.id = :id',
            ['id' => $id],
        );

        return $row ? $this->mapRow($row) : null;
    }

    private function mapRow(array $r): EventDto
    {
        return new EventDto(
            id: $r['id'],
            title: $r['title'],
            description: $r['description'],
            date: $r['date'],
            venueName: $r['venue_name'],
            venueCity: $r['venue_city'],
            priceMin: (int) $r['price_min'],
            priceMax: (int) $r['price_max'],
            priceCurrency: (string) $r['price_currency'],
            status: $r['status'],
        );
    }
}
