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
        $qb = $this->createBaseQueryBuilder()
            ->where('e.status = :status')
            ->setParameter('status', 'published')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('e.date', 'DESC');

        return array_map(fn(array $r) => $this->mapRow($r), $qb->executeQuery()->fetchAllAssociative());
    }

    /** @return EventDto[] */
    public function searchPublished(?string $query, ?string $city, ?\DateTimeImmutable $dateFrom, ?\DateTimeImmutable $dateTo, int $limit, int $offset): array
    {
        $qb = $this->createBaseQueryBuilder()
            ->where('e.status = :status')
            ->setParameter('status', 'published')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('e.date', 'DESC');

        if ($query !== null && $query !== '') {
            $qb->andWhere('(e.title LIKE :q OR e.description LIKE :q)')
                ->setParameter('q', '%' . $query . '%');
        }
        if ($city !== null && $city !== '') {
            $qb->andWhere('v.city LIKE :city')
                ->setParameter('city', '%' . $city . '%');
        }
        if ($dateFrom !== null) {
            $qb->andWhere('e.date >= :df')
                ->setParameter('df', $dateFrom->format('Y-m-d H:i:s'));
        }
        if ($dateTo !== null) {
            $qb->andWhere('e.date <= :dt')
                ->setParameter('dt', $dateTo->format('Y-m-d H:i:s'));
        }

        return array_map(fn(array $r) => $this->mapRow($r), $qb->executeQuery()->fetchAllAssociative());
    }

    public function findById(string $id): ?EventDto
    {
        $qb = $this->createBaseQueryBuilder()
            ->where('e.id = :id')
            ->setParameter('id', $id);

        $row = $qb->executeQuery()->fetchAssociative();

        return $row ? $this->mapRow($row) : null;
    }

    private function createBaseQueryBuilder()
    {
        return $this->connection->createQueryBuilder()
            ->select('
                e.id, e.title, e.description, e.date,
                v.name AS venue_name, v.city AS venue_city,
                e.status,
                COALESCE(MIN(es.price_amount), 0) AS price_min,
                COALESCE(MAX(es.price_amount), 0) AS price_max,
                COALESCE(MIN(es.price_currency), :defaultCurrency) AS price_currency
            ')
            ->from('events', 'e')
            ->join('e', 'venues', 'v', 'v.id = e.venue_id')
            ->leftJoin('e', 'event_seats', 'es', 'es.event_id = e.id')
            ->setParameter('defaultCurrency', 'BYN')
            ->groupBy('e.id, e.title, e.description, e.date, v.name, v.city, e.status');
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
