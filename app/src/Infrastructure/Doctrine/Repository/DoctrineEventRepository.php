<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Event;
use App\Domain\Repository\EventRepositoryInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineEventRepository implements EventRepositoryInterface
{
    /** @var EntityRepository<Event> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(Event::class);
    }

    public function findById(EventId $id): ?Event
    {
        return $this->entityManager->find(Event::class, $id->toString());
    }

    public function findPublished(int $limit, int $offset): array
    {
        return $this->repository->findBy(['status' => EventStatus::Published], ['date' => 'DESC'], $limit, $offset);
    }

    public function searchPublished(
        ?string $query,
        ?string $city,
        ?\DateTimeImmutable $dateFrom,
        ?\DateTimeImmutable $dateTo,
        int $limit,
        int $offset,
    ): array {
        $qb = $this->repository->createQueryBuilder('e')
            ->andWhere('e.status = :status')
            ->setParameter('status', EventStatus::Published)
            ->orderBy('e.date', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($query !== null && $query !== '') {
            $qb->andWhere('(e.title LIKE :query OR e.description LIKE :query)')
                ->setParameter('query', '%' . $query . '%');
        }

        if ($city !== null && $city !== '') {
            $qb->join('e.venue', 'venue')
                ->andWhere('venue.city LIKE :city')
                ->setParameter('city', '%' . $city . '%');
        }

        if ($dateFrom !== null) {
            $qb->andWhere('e.date >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('e.date <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $qb->getQuery()->getResult();
    }

    public function findAll(): array
    {
        return $this->repository->findBy([], ['date' => 'DESC']);
    }

    public function save(Event $event): void
    {
        $this->entityManager->persist($event);
    }
}
