<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Application\Dto\Factory\EventDtoFactory;
use App\Domain\Entity\Event;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\Repository\EventSearchInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

final readonly class DoctrineEventRepository implements EventRepositoryInterface, EventSearchInterface
{
    /** @var EntityRepository<Event> */
    private EntityRepository $repository;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDtoFactory $eventDtoFactory,
    ) {
        $this->repository = $entityManager->getRepository(Event::class);
    }

    public function findById(EventId $id): ?Event
    {
        return $this->entityManager->find(Event::class, $id->toString());
    }

    /**
     * @return array<int, Event>
     */
    public function findAll(): array
    {
        return $this->repository->findBy([], ['date' => 'DESC']);
    }

    public function save(Event $event): void
    {
        $this->entityManager->persist($event);
    }

    /**
     * @return array<int, Event>
     */
    public function findPublished(int $limit, int $offset): array
    {
        return $this->repository->findBy(['status' => EventStatus::Published], ['date' => 'DESC'], $limit, $offset);
    }

    /**
     * @return array<int, Event>
     */
    public function searchPublished(
        ?string $query,
        ?string $city,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
        int $limit,
        ?string $cursor,
    ): array {
        $qb = $this->repository->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', EventStatus::Published)
            ->orderBy('e.date', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->setMaxResults($limit);

        if ($query !== null && $query !== '') {
            $qb->andWhere('(e.title LIKE :query OR e.description LIKE :query)')
                ->setParameter('query', '%'.$query.'%');
        }

        if ($city !== null && $city !== '') {
            $qb->join('e.venue', 'venue')
                ->andWhere('venue.city LIKE :city')
                ->setParameter('city', '%'.$city.'%');
        }

        if ($dateFrom !== null) {
            $qb->andWhere('e.date >= :dateFrom')->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('e.date <= :dateTo')->setParameter('dateTo', $dateTo);
        }

        $this->applyCursor($qb, $cursor);

        $result = $qb->getQuery()->getResult();
        assert(is_array($result));

        return $result;
    }

    public function findPublishedList(int $limit, int $offset): array
    {
        $events = $this->findPublished($limit, $offset);

        return $this->eventDtoFactory->fromEventList($events);
    }

    /**
     * @return array<int, Event>
     */
    public function findAllCursor(int $limit, ?string $cursor): array
    {
        $qb = $this->repository->createQueryBuilder('e')
            ->orderBy('e.date', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->setMaxResults($limit);

        $this->applyCursor($qb, $cursor);

        $result = $qb->getQuery()->getResult();
        assert(is_array($result));

        return $result;
    }

    /**
     * @return array<int, Event>
     */
    public function findPublishedCursor(int $limit, ?string $cursor): array
    {
        $qb = $this->repository->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', EventStatus::Published)
            ->orderBy('e.date', 'DESC')
            ->addOrderBy('e.id', 'DESC')
            ->setMaxResults($limit);

        $this->applyCursor($qb, $cursor);

        $result = $qb->getQuery()->getResult();
        assert(is_array($result));

        return $result;
    }

    /**
     * @return array<int, \App\Application\Dto\EventDto>
     */
    public function search(
        ?string $query,
        ?string $city,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
        int $limit,
        ?string $cursor,
    ): array {
        $events = $this->searchPublished($query, $city, $dateFrom, $dateTo, $limit, $cursor);

        return $this->eventDtoFactory->fromEventList($events);
    }

    private function applyCursor(QueryBuilder $qb, ?string $cursor): void
    {
        if (!$cursor) {
            return;
        }

        $decoded = base64_decode($cursor, true);
        if ($decoded === false) {
            return;
        }
        $parts = explode('|', $decoded);
        if (\count($parts) === 2) {
            try {
                $date = new \DateTimeImmutable($parts[0]);

                $qb->andWhere('(e.date < :cDate) OR (e.date = :cDate AND e.id < :cId)')
                    ->setParameter('cDate', $date)
                    ->setParameter('cId', $parts[1]);
            } catch (\Throwable) {
            }
        }
    }
}
