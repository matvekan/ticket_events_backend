<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModel;

use App\Application\Dto\EventDto;
use App\Application\Port\EventReadRepositoryInterface;
use Doctrine\DBAL\Connection;

final class DoctrineEventReadRepository implements EventReadRepositoryInterface
{
    public function __construct(private readonly Connection $connection) {}

    /** @return EventDto[] */
    public function findPublished(int $limit, int $offset): array
    {
        $sql = <<<SQL
            SELECT e.id, e.title, e.description, e.date,
                   v.name as venue_name, v.city as venue_city,
                   e.status
            FROM events e
            JOIN venues v ON v.id = e.venue_id
            WHERE e.status = 'published'
            ORDER BY e.date DESC
            LIMIT :limit OFFSET :offset
        SQL;

        $rows = $this->connection->fetchAllAssociative($sql, ['limit' => $limit, 'offset' => $offset]);

        return array_map(fn(array $r) => $this->mapRow($r), $rows);
    }

    public function searchPublished(?string $query, ?string $city, ?\DateTimeImmutable $dateFrom, ?\DateTimeImmutable $dateTo, int $limit, int $offset): array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('e.id, e.title, e.description, e.date, v.name as venue_name, v.city as venue_city, e.status')
            ->from('events', 'e')
            ->join('e', 'venues', 'v', 'v.id = e.venue_id')
            ->where('e.status = :status')
            ->setParameter('status', 'published')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('e.date', 'DESC');

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
        $row = $this->connection->fetchAssociative('SELECT e.id, e.title, e.description, e.date, v.name as venue_name, v.city as venue_city, e.status FROM events e JOIN venues v ON v.id = e.venue_id WHERE e.id = :id', ['id' => $id]);
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
            priceMin: 0,
            priceMax: 0,
            priceCurrency: 'BYN',
            status: $r['status'],
        );
    }
}
