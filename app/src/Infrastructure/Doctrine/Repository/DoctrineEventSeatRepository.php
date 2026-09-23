<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\EventSeat;
use App\Domain\Repository\EventSeatRepositoryInterface;
use App\Domain\ValueObject\EventId;
use App\Domain\ValueObject\EventSeatId;
use App\Domain\ValueObject\SeatStatus;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DoctrineEventSeatRepository implements EventSeatRepositoryInterface
{
    /** @var EntityRepository<EventSeat> */
    private readonly EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $entityManager->getRepository(EventSeat::class);
    }

    public function findById(EventSeatId $id): ?EventSeat
    {
        return $this->entityManager->find(EventSeat::class, $id->toString());
    }

    /**
     * @param array<int, EventSeatId> $ids
     * @return array<int, EventSeat>
     */
    public function findByIds(array $ids): array
    {
        $stringIds = array_map(static fn (EventSeatId $id) => $id->toString(), $ids);

        return $this->repository->findBy(['id' => $stringIds]);
    }

    /**
     * @param array<int, EventSeatId> $ids
     * @return array<int, EventSeat>
     */
    public function lockAndFindByIds(array $ids): array
    {
        $stringIds = array_map(static fn (EventSeatId $id) => $id->toString(), $ids);
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('es')
            ->from(EventSeat::class, 'es')
            ->where($qb->expr()->in('es.id', ':ids'))
            ->setParameter('ids', $stringIds);

        $query = $qb->getQuery();
        $query->setLockMode(LockMode::PESSIMISTIC_WRITE);

        /** @var array<int, EventSeat> $result */
        $result = $query->getResult();

        return $result;
    }

    /**
     * @return array<int, EventSeat>
     */
    public function findAvailableByEventId(EventId $eventId): array
    {
        return $this->repository->findBy(['event' => $eventId->toString(), 'status' => SeatStatus::Free]);
    }

    /**
     * @return array<int, EventSeat>
     */
    public function findByEventId(EventId $eventId): array
    {
        return $this->repository->findBy(['event' => $eventId->toString()]);
    }

    public function save(EventSeat $eventSeat): void
    {
        $this->entityManager->persist($eventSeat);
    }
}
